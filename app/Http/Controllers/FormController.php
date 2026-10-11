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

        // Termin od–do i limit zgłoszeń — sprawdzane też przy zapisie (formularz mógł zostać otwarty wcześniej).
        $availability = $formularz->availability();
        if (! $availability['open']) {
            return back()
                ->withErrors(['closed' => $availability['message']])
                ->withInput()
                ->with('_form_slug', $formularz->slug);
        }

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
            $formularz->validationRules((array) $request->input('data', [])),
            $formularz->validationMessages(),
            $formularz->validationAttributes(),
        );

        if ($validator->fails()) {
            // Błędy elementów list (data.pole.0) przypisujemy do całego pola — wtedy trafiają pod nie i do podsumowania.
            $bag = new \Illuminate\Support\MessageBag;
            foreach ($validator->errors()->messages() as $key => $messages) {
                foreach (array_unique($messages) as $message) {
                    $bag->add(preg_replace('/^(data\.[^.]+)\..+$/', '$1', $key), $message);
                }
            }

            return back()
                ->withErrors($bag)
                ->withInput()
                ->with('_form_slug', $formularz->slug);
        }

        // CleanTalk (opcjonalnie, wg ustawień): ocena treści i zachowania nadawcy po stronie usługi.
        // Dopiero po poprawnej walidacji — nie wysyłamy tam niepełnych danych. Awaria usługi = przepuszczamy.
        if (CleanTalkGuard::enabled() && empty($formularz->settings['cleantalk_disabled'])) {
            $verdict = app(CleanTalkGuard::class)->check($request, $formularz->inputFields(), (array) $request->input('data', []));

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
            'data'               => $this->submittedData($request, $formularz),
            'ip_address'         => $request->ip(),
        ]);

        // Przekazanie do SZO. Świadomie PO zapisie lokalnym i bez rzucania
        // wyjątków: zgłoszenie jest już bezpieczne w bazie CMS-a, a niedostępne
        // SZO nie może popsuć potwierdzenia dla użytkownika. Nieudane próby
        // dosyła polecenie `php artisan szo:push-submissions`.
        app(SzoClient::class)->pushSubmission($submission);

        // Doręczenie wg ustawień: e-mail, API (webhook) albo oba. Zapis w panelu jest zawsze.
        if (in_array($formularz->delivery(), ['email', 'both'], true)) {
            $this->sendNotifications($formularz, $submission);
        }
        if (in_array($formularz->delivery(), ['api', 'both'], true)) {
            app(\App\Services\FormWebhook::class)->send($formularz, $submission);
        }

        return back()
            ->with('success', $this->confirmationMessage($formularz))
            ->with('_form_slug', $formularz->slug);
    }

    /**
     * Dane zgłoszenia: tylko znane pola formularza. Załączniki trafiają na prywatny dysk
     * (`local`, poza katalogiem publicznym) — w danych zostaje nazwa, ścieżka, rozmiar i typ;
     * pobranie wyłącznie z panelu.
     */
    private function submittedData(Request $request, FormDefinition $formularz): array
    {
        $input = (array) $request->input('data', []);
        $visible = $formularz->visibility($input);
        $data = [];

        foreach ($formularz->inputFields() as $field) {
            $key = $field['key'];

            // Pole ukryte warunkiem — nie zapisujemy (mogło zostać wypełnione przed zmianą odpowiedzi).
            if (! ($visible[$key] ?? true)) {
                continue;
            }

            if (($field['type'] ?? '') === 'file') {
                $file = $request->file('data.'.$key);
                if ($file && $file->isValid()) {
                    $data[$key] = [
                        'name' => mb_substr($file->getClientOriginalName(), 0, 200),
                        'path' => $file->store('form-uploads/'.$formularz->id, 'local'),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                    ];
                }

                continue;
            }

            if (array_key_exists($key, $input)) {
                $data[$key] = is_array($input[$key]) ? array_values(array_map('strval', $input[$key])) : $input[$key];
            }
        }

        return $data;
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
        foreach ($formularz->inputFields() as $field) {
            if (($field['type'] ?? null) !== 'email') {
                continue;
            }
            $value = trim((string) ($submission->data[$field['key']] ?? ''));

            return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
        }

        return null;
    }
}
