<?php

namespace App\Http\Controllers;

use App\Mail\FormSubmissionNotification;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Services\SzoClient;
use App\Support\CleanTalkGuard;
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

        // CleanTalk (opcjonalnie, wg ustawień): ocena treści i zachowania nadawcy po stronie usługi.
        // Dopiero po poprawnej walidacji — nie wysyłamy tam niepełnych danych. Awaria usługi = przepuszczamy.
        if (CleanTalkGuard::enabled() && empty($formularz->settings['cleantalk_disabled'])) {
            $verdict = app(CleanTalkGuard::class)->check($request, $formularz->normalizedFields(), (array) $request->input('data', []));

            if (! $verdict['allow']) {
                Log::warning('Zablokowano zgłoszenie formularza (CleanTalk)', [
                    'formularz' => $formularz->slug,
                    'komentarz' => $verdict['comment'],
                    'zapytanie' => $verdict['link'],
                    'ip'        => $request->ip(),
                ]);

                return back()
                    ->withErrors(['spam' => 'Zgłoszenie zostało uznane za spam przez zewnętrzny filtr antyspamowy. '
                        .'Jeśli to pomyłka, zmień treść wiadomości lub skontaktuj się z nami inną drogą.'])
                    ->withInput()
                    ->with('_form_slug', $formularz->slug);
            }
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

        $this->sendNotifications($formularz, $submission);

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

    /**
     * Powiadomienia e-mail o zgłoszeniu: do redakcji (adres + DW z ustawień
     * formularza, Reply-To z pola e-mail zgłaszającego) oraz opcjonalna kopia
     * dla osoby zgłaszającej. Mailer wg ustawień formularza: dziedzicz
     * (domyślnie Microsoft Graph, gdy skonfigurowany), wymuś Graph albo
     * domyślny transport serwisu. Błąd wysyłki nie psuje potwierdzenia —
     * zgłoszenie jest już w bazie; problem trafia do logu.
     */
    private function sendNotifications(FormDefinition $formularz, FormSubmission $submission): void
    {
        $settings = $formularz->settings ?? [];
        $site = SiteSetting::current();

        $mailerName = match ($settings['mailer'] ?? '') {
            'msgraph' => $site->msGraphConfigured() ? 'msgraph' : null,
            'default' => null,
            default   => $site->formsMailer(),
        };
        $mailer = $mailerName ? Mail::mailer($mailerName) : Mail::mailer();

        $submitterEmail = $this->submitterEmail($formularz, $submission);

        $to = array_values(array_filter(array_map('trim', explode(',', (string) ($settings['notification_email'] ?? ''))), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        $cc = array_values(array_filter(array_map('trim', explode(',', (string) ($settings['notification_cc'] ?? ''))), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        if ($to !== []) {
            try {
                $mail = new FormSubmissionNotification($formularz, $submission);
                if ($cc !== []) {
                    $mail->cc($cc);
                }
                if ($submitterEmail && ($settings['reply_to_submitter'] ?? true)) {
                    $mail->replyTo($submitterEmail);
                }
                $mailer->to($to)->send($mail);
            } catch (\Throwable $e) {
                Log::error('Formularz: nie udało się wysłać powiadomienia o zgłoszeniu.', [
                    'form' => $formularz->slug, 'submission' => $submission->id, 'mailer' => $mailerName ?? 'default', 'error' => $e->getMessage(),
                ]);
            }
        }

        if ($submitterEmail && ! empty($settings['send_copy_to_submitter'])) {
            try {
                $mailer->to($submitterEmail)->send(new FormSubmissionNotification($formularz, $submission, forSubmitter: true));
            } catch (\Throwable $e) {
                Log::error('Formularz: nie udało się wysłać kopii zgłoszenia do nadawcy.', [
                    'form' => $formularz->slug, 'submission' => $submission->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** Adres e-mail osoby zgłaszającej: wartość pierwszego pola typu „e-mail”. */
    private function submitterEmail(FormDefinition $formularz, FormSubmission $submission): ?string
    {
        foreach ($formularz->normalizedFields() as $field) {
            if (($field['type'] ?? null) !== 'email') {
                continue;
            }
            $value = trim((string) ($submission->data[$field['key']] ?? ''));

            return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
        }

        return null;
    }
}
