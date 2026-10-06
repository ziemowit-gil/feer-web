<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Services\SzoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormularzeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q', '');

        $forms = FormDefinition::withCount('submissions', 'unreadSubmissions')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.formularze.index', [
            'forms'  => $forms,
            'q'      => $search,
        ]);
    }

    public function create()
    {
        return view('admin.formularze.form', [
            'form'     => new FormDefinition(['fields' => [], 'settings' => []]),
            'types'    => FormDefinition::FIELD_TYPES,
            'szoForms' => $this->szoForms(),
        ]);
    }

    /**
     * Formularze zdefiniowane w SZO — do wyboru przy podpinaniu.
     *
     * Pusta lista (integracja wyłączona albo SZO nie odpowiada) nie może
     * zablokować edycji formularza, więc widok po prostu pokazuje wtedy
     * instrukcję zamiast listy.
     */
    protected function szoForms(): array
    {
        return app(SzoClient::class)->forms();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $form = FormDefinition::create($data);

        return redirect()
            ->route('admin.formularze.edit', $form)
            ->with('status', 'Formularz został utworzony.');
    }

    public function edit(FormDefinition $formularz)
    {
        return view('admin.formularze.form', [
            'form'     => $formularz,
            'types'    => FormDefinition::FIELD_TYPES,
            'szoForms' => $this->szoForms(),
        ]);
    }

    public function update(Request $request, FormDefinition $formularz)
    {
        $formularz->update($this->validated($request, $formularz));

        return redirect()
            ->route('admin.formularze.edit', $formularz)
            ->with('status', 'Formularz został zaktualizowany.');
    }

    public function destroy(FormDefinition $formularz)
    {
        $formularz->delete();

        return redirect()
            ->route('admin.formularze.index')
            ->with('status', 'Formularz usunięty.');
    }

    /** Lista zgłoszeń nadesłanych do formularza. */
    public function zgloszenia(FormDefinition $formularz)
    {
        $formularz->submissions()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $submissions = $formularz->submissions()
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.formularze.zgloszenia', [
            'form'        => $formularz,
            'submissions' => $submissions,
        ]);
    }

    /** Eksport zgłoszeń do CSV. */
    public function eksportZgloszen(FormDefinition $formularz)
    {
        $fields      = $formularz->normalizedFields();
        $submissions = $formularz->submissions()->orderByDesc('created_at')->get();
        $filename    = 'formularz-' . $formularz->slug . '-zgloszenia.csv';

        return response()->streamDownload(function () use ($fields, $submissions) {
            $handle = fopen('php://output', 'w');

            // BOM dla poprawnych polskich znaków w Excelu
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = array_merge(
                ['ID', 'Data'],
                array_column($fields, 'label'),
            );
            fputcsv($handle, $headers, ';');

            foreach ($submissions as $sub) {
                $row = [$sub->id, $sub->created_at->format('Y-m-d H:i')];
                foreach ($fields as $field) {
                    $row[] = $sub->data[$field['key']] ?? '';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Usuń pojedyncze zgłoszenie. */
    public function destroyZgloszenie(FormDefinition $formularz, FormSubmission $submission)
    {
        abort_unless($submission->form_definition_id === $formularz->id, 404);
        $submission->delete();

        return redirect()
            ->route('admin.formularze.zgloszenia', $formularz)
            ->with('status', 'Zgłoszenie usunięte.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validated(Request $request, ?FormDefinition $existing = null): array
    {
        $request->validate([
            'title'                          => 'required|string|max:255',
            'slug'                           => [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9\-]+$/',
                \Illuminate\Validation\Rule::unique('form_definitions', 'slug')
                    ->ignore($existing?->id),
            ],
            'description'                    => 'nullable|string|max:2000',
            'is_active'                      => 'boolean',
            'fields'                         => 'nullable|array',
            'fields.*.label'                 => 'required|string|max:255',
            'fields.*.type'                  => 'required|in:' . implode(',', array_keys(FormDefinition::FIELD_TYPES)),
            'fields.*.required'              => 'boolean',
            'fields.*.placeholder'           => 'nullable|string|max:255',
            'fields.*.options'               => 'nullable|string|max:2000',
            'fields.*.help_text'             => 'nullable|string|max:500',
            'settings.confirmation_message'  => 'nullable|string|max:1000',
            'settings.notification_email'    => 'nullable|string|max:500',
            'settings.notification_cc'       => 'nullable|string|max:500',
            'settings.notification_subject'  => 'nullable|string|max:200',
            'settings.mailer'                => 'nullable|in:,msgraph,default',
            'settings.reply_to_submitter'    => 'nullable|boolean',
            'settings.send_copy_to_submitter' => 'nullable|boolean',
            'settings.submitter_copy_message' => 'nullable|string|max:1000',
            'settings.szo_form_slug'         => 'nullable|string|max:120',
        ], [], [
            'title'       => 'nazwa formularza',
            'slug'        => 'identyfikator URL',
            'description' => 'opis',
            'settings.notification_email' => 'e-mail do powiadomień',
            'settings.notification_cc'    => 'kopia (DW)',
        ]);

        // Listy adresów rozdzielone przecinkami: każdy musi być poprawnym e-mailem.
        foreach (['settings.notification_email' => 'e-mail do powiadomień', 'settings.notification_cc' => 'kopia (DW)'] as $key => $label) {
            $raw = (string) $request->input($key, '');
            $bad = collect(explode(',', $raw))->map(fn ($e) => trim($e))->filter()
                ->reject(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->values();
            if ($bad->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $key => 'Pole '.$label.' zawiera niepoprawny adres: '.$bad->implode(', ').'. Adresy rozdziel przecinkami.',
                ]);
            }
        }

        $fields = collect($request->input('fields', []))
            ->filter(fn ($f) => ! empty($f['label']))
            ->map(fn ($f) => [
                'label'       => $f['label'],
                'type'        => $f['type'] ?? 'text',
                'required'    => ! empty($f['required']),
                'placeholder' => $f['placeholder'] ?? null,
                'options'     => $f['options'] ?? null,
                'help_text'   => $f['help_text'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'title'       => $request->input('title'),
            'slug'        => $request->input('slug'),
            'description' => $request->input('description'),
            'is_active'   => $request->boolean('is_active'),
            'fields'      => $fields,
            'settings'    => [
                'confirmation_message' => $request->input('settings.confirmation_message'),
                'notification_email'   => $request->input('settings.notification_email'),
                'notification_cc'      => $request->input('settings.notification_cc'),
                'notification_subject' => $request->input('settings.notification_subject'),
                // Transport powiadomień: '' = dziedzicz (domyślnie Microsoft Graph,
                // gdy skonfigurowany w Ustawienia → Poczta), 'msgraph' = wymuś Graph,
                // 'default' = zawsze domyślny tryb wysyłki serwisu.
                'mailer'               => in_array($request->input('settings.mailer'), ['msgraph', 'default'], true) ? $request->input('settings.mailer') : '',
                'reply_to_submitter'   => $request->boolean('settings.reply_to_submitter'),
                'send_copy_to_submitter' => $request->boolean('settings.send_copy_to_submitter'),
                'submitter_copy_message' => $request->input('settings.submitter_copy_message'),
                // Slug formularza po stronie SZO. Puste = zgłoszenia zostają
                // wyłącznie w CMS-ie; podpięcie jest świadomą decyzją redaktora.
                'szo_form_slug'        => $request->input('settings.szo_form_slug'),
            ],
        ];
    }
}
