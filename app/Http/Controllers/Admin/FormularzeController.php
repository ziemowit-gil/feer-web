<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Services\SzoClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $fields      = $formularz->inputFields();
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
                    $row[] = FormDefinition::displayValue($field, $sub->data[$field['key']] ?? null);
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
        $this->deleteFiles($formularz, $submission);
        $submission->delete();

        return redirect()
            ->route('admin.formularze.zgloszenia', $formularz)
            ->with('status', 'Zgłoszenie usunięte.');
    }

    /** Pobranie załącznika ze zgłoszenia (prywatny dysk — tylko z panelu). */
    public function plik(FormDefinition $formularz, FormSubmission $submission, string $key)
    {
        abort_unless($submission->form_definition_id === $formularz->id, 404);
        $file = $submission->data[$key] ?? null;
        abort_unless(is_array($file) && filled($file['path'] ?? null) && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name'] ?? basename($file['path']));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Usuwa z dysku załączniki zgłoszenia. */
    private function deleteFiles(FormDefinition $formularz, FormSubmission $submission): void
    {
        foreach ($formularz->inputFields() as $field) {
            $file = $submission->data[$field['key']] ?? null;
            if (($field['type'] ?? '') === 'file' && is_array($file) && filled($file['path'] ?? null)) {
                Storage::disk('local')->delete($file['path']);
            }
        }
    }

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
            'fields.*.accept'                => 'nullable|string|max:200',
            'fields.*.uid'                   => 'nullable|string|max:20',
            'fields.*.show_if_uid'           => 'nullable|string|max:20',
            'fields.*.show_if_value'         => 'nullable|string|max:255',
            'fields.*.max_mb'                => 'nullable|integer|min:1|max:' . FormDefinition::FILE_MAX_MB,
            'fields.*.url'                   => ['nullable', 'string', 'max:500', 'regex:~^(https?://\S+|/(?!/)\S*)$~i'],
            'fields.*.new_tab'               => 'nullable|boolean',
            'settings.redirect_url'          => ['nullable', 'string', 'max:500', 'regex:~^(https?://\S+|/(?!/)\S*)$~i'],
            'fields.*.krs_fill'              => 'nullable|in:' . implode(',', array_keys(\App\Services\KrsClient::FILL_KEYS)),
            'settings.confirmation_message'  => 'nullable|string|max:1000',
            'settings.notification_email'    => 'nullable|string|max:500',
            'settings.notification_cc'       => 'nullable|string|max:500',
            'settings.notification_subject'  => 'nullable|string|max:200',
            'settings.mailer'                => 'nullable|in:,msgraph,default',
            'settings.reply_to_submitter'    => 'nullable|boolean',
            'settings.send_copy_to_submitter' => 'nullable|boolean',
            'settings.submitter_copy_message' => 'nullable|string|max:1000',
            'settings.cleantalk_disabled'    => 'nullable|boolean',
            'settings.szo_form_slug'         => 'nullable|string|max:120',
            'settings.delivery'              => 'nullable|in:' . implode(',', array_keys(FormDefinition::DELIVERY)),
            'settings.webhook_url'           => ['nullable', 'url:http,https', 'max:500', 'required_if:settings.delivery,api,both'],
            'settings.webhook_secret'        => 'nullable|string|max:200',
            'settings.open_from'             => 'nullable|date',
            'settings.open_until'            => 'nullable|date|after:settings.open_from',
            'settings.max_submissions'       => 'nullable|integer|min:1|max:100000',
            'settings.show_remaining'        => 'nullable|boolean',
            'settings.closed_message'        => 'nullable|string|max:1000',
        ], [], [
            'title'       => 'nazwa formularza',
            'slug'        => 'identyfikator URL',
            'description' => 'opis',
            'settings.notification_email' => 'e-mail do powiadomień',
            'settings.notification_cc'    => 'kopia (DW)',
            'settings.open_from'          => 'początek zbierania zgłoszeń',
            'settings.open_until'         => 'koniec zbierania zgłoszeń',
            'settings.max_submissions'    => 'limit zgłoszeń',
            'settings.webhook_url'        => 'adres API',
            'settings.redirect_url'       => 'adres przekierowania po wysłaniu',
            'fields.*.url'                => 'adres przycisku',
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

        // Klucze SZO pól lustrzanych (SzoFormMirror) nie są edytowalne w panelu — przenosimy je po uid.
        $szoNames = collect($existing?->fields ?? [])->filter(fn ($f) => filled($f['szo_name'] ?? null) && filled($f['uid'] ?? null))
            ->mapWithKeys(fn ($f) => [$f['uid'] => $f['szo_name']])->all();

        $fields = collect($request->input('fields', []))
            ->filter(fn ($f) => ! empty($f['label']))
            ->map(fn ($f) => [
                'label'       => $f['label'],
                'type'        => $f['type'] ?? 'text',
                'required'    => ! empty($f['required']),
                'placeholder' => $f['placeholder'] ?? null,
                'options'     => $f['options'] ?? null,
                'help_text'   => $f['help_text'] ?? null,
                'accept'      => ($f['type'] ?? '') === 'file' ? (trim((string) ($f['accept'] ?? '')) ?: null) : null,
                'max_mb'      => ($f['type'] ?? '') === 'file' && filled($f['max_mb'] ?? null) ? (int) $f['max_mb'] : null,
                // „Tekst z przyciskiem”: adres przejścia i czy w nowej karcie.
                'url'         => ($f['type'] ?? '') === 'link' ? FormDefinition::safeUrl($f['url'] ?? null) : null,
                'new_tab'     => ($f['type'] ?? '') === 'link' && ! empty($f['new_tab']),
                // Pole uzupełniane danymi z pola „Numer KRS” (np. nazwa, NIP, adres).
                'krs_fill'    => in_array($f['type'] ?? 'text', FormDefinition::KRS_FILLABLE_TYPES, true) && array_key_exists((string) ($f['krs_fill'] ?? ''), \App\Services\KrsClient::FILL_KEYS) ? $f['krs_fill'] : null,
                // Stały identyfikator pola — warunki „Pokaż tylko, gdy…” przetrwają zmianę etykiety.
                'uid'         => preg_match('/^[a-z0-9]{4,20}$/i', (string) ($f['uid'] ?? '')) ? $f['uid'] : Str::lower(Str::random(8)),
                'show_if_uid'   => ($f['type'] ?? '') !== 'step' && filled($f['show_if_uid'] ?? null) ? (string) $f['show_if_uid'] : null,
                'show_if_value' => ($f['type'] ?? '') !== 'step' && filled($f['show_if_uid'] ?? null) ? trim((string) ($f['show_if_value'] ?? '')) : null,
            ])
            ->map(fn ($f) => isset($szoNames[$f['uid']]) ? $f + ['szo_name' => $szoNames[$f['uid']]] : $f)
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
                // true = ten formularz nie jest sprawdzany w CleanTalk (np. formularz wewnętrzny, bez ryzyka spamu).
                'cleantalk_disabled'   => $request->boolean('settings.cleantalk_disabled'),
                // Doręczenie: e-mail, API (webhook) albo oba; sekret podpisuje treść (HMAC SHA-256).
                'delivery'             => array_key_exists((string) $request->input('settings.delivery'), FormDefinition::DELIVERY) ? $request->input('settings.delivery') : 'email',
                'webhook_url'          => trim((string) $request->input('settings.webhook_url')) ?: null,
                'webhook_secret'       => $request->filled('settings.webhook_secret') ? $request->input('settings.webhook_secret') : ($existing?->settings['webhook_secret'] ?? null),
                // Terminy i limity: zbieranie od–do, limit zgłoszeń (np. zapisy z ograniczoną liczbą miejsc).
                'open_from'            => $request->input('settings.open_from') ?: null,
                'open_until'           => $request->input('settings.open_until') ?: null,
                'max_submissions'      => $request->filled('settings.max_submissions') ? (int) $request->input('settings.max_submissions') : null,
                'show_remaining'       => $request->boolean('settings.show_remaining'),
                'closed_message'       => $request->input('settings.closed_message'),
                // Po wysłaniu: przejście na inny adres zamiast komunikatu na tej samej stronie.
                'redirect_url'         => FormDefinition::safeUrl($request->input('settings.redirect_url')),
            ],
        ];
    }
}
