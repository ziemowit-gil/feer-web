<?php

namespace App\Support;

use CleanTalk\CleantalkAntispam;
use CleanTalk\HTTP\CleantalkResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dodatkowa warstwa ochrony formularzy przed spamem — usługa CleanTalk
 * (pakiet cleantalk/laravel-antispam): analiza zachowania odwiedzającego
 * (skrypt bot-detector na stronie) i ocena treści po stronie serwera CleanTalk.
 *
 * Z pakietu używamy konfiguracji (`config/cleantalk.php`), skryptu frontowego,
 * adresu API i parsera odpowiedzi. Samo zapytanie wysyłamy własnym klientem HTTP,
 * bo warstwa HTTP pakietu ma limit czasu 50 s — niedostępny CleanTalk blokowałby
 * wtedy wysyłanie formularza. Tu limit to kilka sekund, a każda awaria oznacza
 * „przepuść” (fail-open): zgłoszenie człowieka ważniejsze niż pojedynczy spam,
 * zwłaszcza że działa też lokalny SpamGuard.
 *
 * Do CleanTalk trafiają: treść zgłoszenia, adres e-mail i imię nadawcy, jego adres
 * IP, adres strony odsyłającej oraz token skryptu — patrz informacja w ustawieniach.
 */
class CleanTalkGuard
{
    /** Najdłuższy czas oczekiwania na odpowiedź CleanTalk (s). */
    private const TIMEOUT = 5;

    /** Dokumentowany przez CleanTalk adres testowy — zawsze oceniany jako spam. */
    public const TEST_SPAM_EMAIL = 'stop_email@example.com';

    /** Włączone i skonfigurowane (klucz dostępu z panelu albo z .env). */
    public static function enabled(): bool
    {
        return (bool) config('cleantalk.enabled') && filled(config('cleantalk.apikey'));
    }

    /**
     * Ocenia zgłoszenie formularza.
     *
     * @param  array<int, array<string, mixed>>  $fields  pola formularza (FormDefinition::normalizedFields())
     * @param  array<string, mixed>  $data  wartości wpisane przez użytkownika, klucze jak `key` pól
     * @return array{allow: bool, comment: ?string, error: ?string, link: ?string}
     */
    public function check(Request $request, array $fields, array $data): array
    {
        $email = '';
        $nick = '';
        $parts = [];

        foreach ($fields as $field) {
            $value = $data[$field['key']] ?? null;
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $type = $field['type'] ?? 'text';
            if ($type === 'email' && $email === '') {
                $email = $value;
            } elseif ($nick === '' && $type === 'text' && preg_match('/imi[eę]|nazwisk|name|podpis/iu', (string) ($field['label'] ?? ''))) {
                $nick = $value;
            } elseif (in_array($type, ['text', 'textarea'], true)) {
                $parts[] = $value;
            }
        }

        return $this->checkMessage($request, implode("\n", $parts), $email, $nick);
    }

    /**
     * Ocenia pojedynczą wiadomość (formularz kontaktowy i inne formularze o stałych polach).
     *
     * @return array{allow: bool, comment: ?string, error: ?string, link: ?string}
     */
    public function checkMessage(Request $request, string $message, string $email = '', string $nickname = ''): array
    {
        return $this->send([
            'method_name' => 'check_message',
            'auth_key' => (string) config('cleantalk.apikey'),
            'message' => $message,
            'sender_nickname' => $nickname,
            'sender_email' => $email,
            // IP z Laravela (z uwzględnieniem zaufanych proxy), a nie z nagłówków wpisanych przez klienta.
            'sender_ip' => (string) $request->ip(),
            'js_on' => filled($request->input(CleantalkAntispam::EVENT_TOKEN_FIELD_NAME)) ? 1 : 0,
            'event_token' => (string) $request->input(CleantalkAntispam::EVENT_TOKEN_FIELD_NAME, ''),
            'agent' => 'php-cleantalk-check',
            'sender_info' => json_encode(['REFFERRER' => (string) $request->headers->get('referer', '')]),
        ]);
    }

    /**
     * Sprawdza klucz dostępu (panel → „Sprawdź klucz”): wysyła wiadomość od adresu testowego,
     * który CleanTalk zawsze ocenia jako spam. Poprawny klucz = odrzucenie bez błędu.
     *
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public static function diagnose(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return ['ok' => false, 'level' => 'error', 'message' => 'Wpisz klucz dostępu CleanTalk (Access key).'];
        }

        $result = (new self)->send([
            'method_name' => 'check_message',
            'auth_key' => $key,
            'message' => 'Test połączenia weCMS',
            'sender_nickname' => 'Test',
            'sender_email' => self::TEST_SPAM_EMAIL,
            'sender_ip' => '127.0.0.1',
            'js_on' => 1,
            'event_token' => '',
            'agent' => 'php-cleantalk-check',
            'sender_info' => json_encode(['REFFERRER' => '']),
        ], silent: true);

        if ($result['error']) {
            return ['ok' => false, 'level' => 'error', 'message' => 'CleanTalk odrzucił zapytanie: '.$result['error']];
        }
        if (! $result['allow']) {
            return ['ok' => true, 'level' => 'success', 'message' => 'Klucz działa: CleanTalk poprawnie rozpoznał wiadomość testową jako spam.'];
        }

        return ['ok' => false, 'level' => 'warning', 'message' => 'Połączenie działa, ale wiadomość testowa nie została rozpoznana jako spam. Sprawdź, czy klucz należy do właściwej usługi.'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{allow: bool, comment: ?string, error: ?string, link: ?string}
     */
    private function send(array $payload, bool $silent = false): array
    {
        $pass = ['allow' => true, 'comment' => null, 'error' => null, 'link' => null];

        try {
            $response = Http::withBody(json_encode($payload), 'application/json')
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(self::TIMEOUT)
                ->post(CleantalkAntispam::MODERATE_URL);

            if ($response->failed()) {
                $pass['error'] = 'HTTP '.$response->status();
                $silent || Log::warning('[CleanTalk] Serwer odpowiedział błędem.', ['status' => $response->status()]);

                return $pass;
            }

            $parsed = new CleantalkResponse(json_decode($response->body()), null);
        } catch (Throwable $e) {
            // Brak połączenia, przekroczony czas, uszkodzona odpowiedź — przepuszczamy.
            $pass['error'] = $e->getMessage();
            $silent || Log::warning('[CleanTalk] Nie udało się sprawdzić zgłoszenia: '.$e->getMessage());

            return $pass;
        }

        if ((int) $parsed->errno !== 0) {
            // Błąd po stronie usługi (zły klucz, wyczerpany limit, wygasła subskrypcja) — też przepuszczamy.
            $pass['error'] = trim((string) $parsed->errstr) ?: 'błąd '.$parsed->errno;
            $silent || Log::warning('[CleanTalk] Usługa zwróciła błąd.', ['errno' => $parsed->errno, 'error' => $pass['error']]);

            return $pass;
        }

        return [
            'allow' => (bool) (int) $parsed->allow,
            'comment' => $parsed->comment,
            'error' => null,
            'link' => $parsed->id ? 'https://cleantalk.org/my/show_requests?request_id='.$parsed->id : null,
        ];
    }
}
