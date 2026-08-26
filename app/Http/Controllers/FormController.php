<?php

namespace App\Http\Controllers;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Services\SzoClient;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    public function show(FormDefinition $formularz)
    {
        abort_unless($formularz->is_active, 404);

        return view('formularz.show', [
            'form'     => $formularz,
            'settings' => SiteSetting::current(),
        ]);
    }

    public function store(Request $request, FormDefinition $formularz)
    {
        abort_unless($formularz->is_active, 404);

        if ($spam = SpamGuard::inspect($request, $request->input('data', []), 'formularz:' . $formularz->slug)) {
            Log::warning('Zablokowano zgłoszenie formularza jako spam', [
                'formularz' => $formularz->slug,
                'powód'     => $spam['reason'],
                'ip'        => $request->ip(),
            ]);

            // Bot dostaje zwykłe potwierdzenie — nie wie, że go odfiltrowano.
            // Filtry treści mogą trafić w człowieka, więc tam pokazujemy błąd.
            return $spam['silent']
                ? back()
                    ->with('success', $this->confirmationMessage($formularz))
                    ->with('_form_slug', $formularz->slug)
                : back()
                    ->withErrors(['spam' => $spam['message']])
                    ->withInput()
                    ->with('_form_slug', $formularz->slug);
        }

        $validator = Validator::make(
            $request->all(),
            $formularz->validationRules(),
            [],
            $formularz->validationAttributes(),
        );

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('_form_slug', $formularz->slug);
        }

        $submission = FormSubmission::create([
            'form_definition_id' => $formularz->id,
            'data'               => $request->input('data', []),
            'ip_address'         => $request->ip(),
        ]);

        // Przekazanie do SZO. Świadomie PO zapisie lokalnym i bez rzucania
        // wyjątków: zgłoszenie jest już bezpieczne w bazie CMS-a, a niedostępne
        // SZO nie może popsuć potwierdzenia dla użytkownika. Nieudane próby
        // dosyła polecenie `php artisan szo:push-submissions`.
        app(SzoClient::class)->pushSubmission($submission);

        $notificationEmail = $formularz->settings['notification_email'] ?? null;
        if (filled($notificationEmail)) {
            $this->sendNotification($formularz, $submission, $notificationEmail);
        }

        return back()
            ->with('success', $this->confirmationMessage($formularz))
            ->with('_form_slug', $formularz->slug);
    }

    /** Komunikat potwierdzenia: własny z ustawień formularza albo domyślny. */
    private function confirmationMessage(FormDefinition $formularz): string
    {
        return filled($formularz->settings['confirmation_message'] ?? null)
            ? $formularz->settings['confirmation_message']
            : 'Dziękujemy! Twoje zgłoszenie zostało przyjęte.';
    }

    private function sendNotification(FormDefinition $formularz, FormSubmission $submission, string $to): void
    {
        $fields = $formularz->normalizedFields();
        $data   = $submission->data;

        $lines = collect($fields)->map(fn ($f) => [
            'label' => $f['label'],
            'value' => $data[$f['key']] ?? '—',
        ])->all();

        Mail::raw(
            implode("\n", array_map(fn ($l) => $l['label'] . ': ' . $l['value'], $lines)),
            function ($msg) use ($formularz, $to) {
                $msg->to($to)->subject('Nowe zgłoszenie: ' . $formularz->title);
            }
        );
    }
}
