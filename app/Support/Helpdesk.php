<?php

namespace App\Support;

use App\Models\LicenseStatus;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Klient API licencji Helpdesku Centralnego — kontrola licencji tej instalacji
 * weCMS (aktywacja + heartbeat per instance_id). Ten sam wzorzec integracji,
 * co opisany w README Helpdesku dla produktu "ShowMe" (sekcja "Integracja
 * z vcard-cms"): `POST /api/v1/licenses/activate|validate|deactivate`,
 * uwierzytelnienie samym kluczem licencji (bez tokenu Bearer), throttle
 * 30/min po stronie Helpdesku.
 *
 * ─── DLACZEGO TAK ──────────────────────────────────────────────────────────
 * Helpdesk stoi pod innym adresem — każde wywołanie może się nie udać (sieć,
 * wdrożenie, awaria). Status licencji w panelu MUSI się dać wyświetlić nawet
 * wtedy, więc: krótki timeout, żaden wyjątek nie wychodzi na zewnątrz (błąd
 * ląduje w `last_error` na LicenseStatus), a poprzedni znany status zostaje,
 * dopóki kolejne udane sprawdzenie go nie zmieni — chwilowa niedostępność
 * Helpdesku nigdy sama w sobie nie unieważnia licencji.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class Helpdesk
{
    public function enabled(): bool
    {
        return ! $this->skippedForThisHost()
            && filled(config('helpdesk.url')) && filled(config('helpdesk.license_key'));
    }

    /** Zgłoszenia (panel "Pomoc") mają osobny token Bearer — licencja i zgłoszenia można włączyć niezależnie. */
    public function ticketsEnabled(): bool
    {
        return ! $this->skippedForThisHost()
            && filled(config('helpdesk.url')) && filled(config('helpdesk.api_token'));
    }

    /**
     * Środowiska lokalne/testowe nie mają realnej licencji ani nie powinny
     * zaśmiecać Helpdesku zgłoszeniami — sprawdzamy adres z APP_URL (nie
     * request()->getHost(): ta klasa jest wywoływana też z konsoli/schedulera,
     * bez żadnego bieżącego żądania HTTP).
     */
    private function skippedForThisHost(): bool
    {
        $host = (string) parse_url(config('app.url', ''), PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || str_starts_with($host, 'testy.');
    }

    /**
     * Aktywuje tę instalację (pierwsze uruchomienie integracji) albo — jeśli
     * już aktywowana — po prostu odświeża status (heartbeat). Wywoływane przez
     * `php artisan helpdesk:sync` (scheduler, co 5 minut).
     */
    public function sync(): LicenseStatus
    {
        $status = LicenseStatus::current();

        if (! $this->enabled()) {
            return $status;
        }

        return $status->activated
            ? $this->heartbeat($status)
            : $this->activate($status);
    }

    private function activate(LicenseStatus $status): LicenseStatus
    {
        $this->call('activate', $status, markActivated: true);

        return $status;
    }

    private function heartbeat(LicenseStatus $status): LicenseStatus
    {
        $this->call('validate', $status);

        return $status;
    }

    /** Zwalnia slot aktywacji w Helpdesku (np. przed przeniesieniem instalacji na inny serwer). */
    public function deactivate(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $status = LicenseStatus::current();

        try {
            $this->request()->post($this->endpoint('deactivate'), [
                'license_key' => config('helpdesk.license_key'),
                'instance_id' => $status->instance_id,
            ]);
        } catch (Throwable $e) {
            Log::warning('[Helpdesk] Nie udało się dezaktywować licencji: '.$e->getMessage());
        }

        $status->update(['activated' => false]);
    }

    /**
     * Wspólna logika activate/validate — oba zwracają ten sam kształt
     * odpowiedzi (patrz README Helpdesku: {valid, reason, license}).
     * Zwraca null przy błędzie sieci/nieoczekiwanej odpowiedzi — poprzedni
     * status w bazie zostaje wtedy nietknięty (patrz docblock klasy).
     */
    private function call(string $action, LicenseStatus $status, bool $markActivated = false): ?array
    {
        try {
            $response = $this->request()->post($this->endpoint($action), [
                'license_key' => config('helpdesk.license_key'),
                'instance_id' => $status->instance_id,
                'instance_name' => config('app.name').' ('.parse_url(config('app.url'), PHP_URL_HOST).')',
            ]);

            if (! $response->successful() && $response->status() !== 422) {
                // 422 to udokumentowana odpowiedź Helpdesku dla nieważnej licencji
                // (patrz README: "Odpowiedź: {valid: false, ...}, status 422") —
                // to JEST poprawna, zdatna do odczytania odpowiedź, nie błąd transportu.
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $data = $response->json();

            $status->update([
                'valid' => (bool) ($data['valid'] ?? false),
                'reason' => $data['reason'] ?? null,
                'product' => $data['license']['product'] ?? null,
                'valid_until' => $data['license']['valid_until'] ?? null,
                'customer_email' => $data['license']['customer_email'] ?? null,
                'last_checked_at' => now(),
                'last_error' => null,
                'activated' => $markActivated ? true : $status->activated,
            ]);

            return $data;
        } catch (Throwable $e) {
            Log::warning("[Helpdesk] Sprawdzenie licencji ({$action}) nie powiodło się: ".$e->getMessage());
            $status->update(['last_error' => $e->getMessage(), 'last_checked_at' => now()]);

            return null;
        }
    }

    /**
     * Wysyła zgłoszenie panelu "Pomoc" — pierwsza wiadomość (nowe zgłoszenie)
     * albo kolejna (dosłanie do istniejącego wątku, ten sam `external_id`;
     * patrz README Helpdesku: "Ponowny POST z tym samym external_id dokleja
     * wiadomość do istniejącego zgłoszenia"). Idempotentne per `$messageId`.
     */
    public function submitTicket(SupportTicket $ticket, string $body, string $messageId): bool
    {
        if (! $this->ticketsEnabled()) {
            $ticket->update(['submit_error' => 'Integracja zgłoszeń nieskonfigurowana (HELPDESK_API_TOKEN).']);

            return false;
        }

        try {
            $response = $this->apiRequest()->post($this->apiEndpoint('tickets'), [
                'external_id' => $ticket->external_id,
                'message_id' => $messageId,
                'subject' => $ticket->subject,
                'message' => $body,
                'requester' => [
                    'name' => $ticket->submittedBy?->name,
                    'email' => $ticket->submittedBy?->email,
                ],
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}: ".$response->body());
            }

            $data = $response->json();

            $ticket->update([
                'remote_id' => $data['id'] ?? $ticket->remote_id,
                'reference' => $data['reference'] ?? $ticket->reference,
                'status' => $data['status'] ?? $ticket->status,
                'last_synced_at' => now(),
                'submit_error' => null,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('[Helpdesk] Wysyłka zgłoszenia nie powiodła się: '.$e->getMessage());
            $ticket->update(['submit_error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Dociąga odpowiedzi obsługi dla jednego zgłoszenia — wywoływane przez
     * `helpdesk:sync` dla każdego zgłoszenia, które ma już `remote_id`.
     * Pomija wiadomości sender_type=customer — to nasze własne, już zapisane
     * lokalnie w chwili wysyłki (patrz submitTicket()); dedukuje resztę po
     * `remote_message_id` (id wiadomości w Helpdesku), żeby kolejne sync nie
     * dublowały tego, co już pobrano.
     */
    public function pullTicketUpdates(SupportTicket $ticket): void
    {
        if (! $this->ticketsEnabled() || ! $ticket->remote_id) {
            return;
        }

        try {
            $response = $this->apiRequest()->get($this->apiEndpoint("tickets/{$ticket->remote_id}"));

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $data = $response->json();

            $ticket->update([
                'status' => $data['status'] ?? $ticket->status,
                'reference' => $data['reference'] ?? $ticket->reference,
                'last_synced_at' => now(),
            ]);

            foreach ($data['messages'] ?? [] as $message) {
                if (($message['sender_type'] ?? null) === 'customer') {
                    continue;
                }

                $remoteMessageId = (string) ($message['id'] ?? '');
                if ($remoteMessageId === '' || $ticket->messages()->where('remote_message_id', $remoteMessageId)->exists()) {
                    continue;
                }

                $ticket->messages()->create([
                    'sender_type' => 'agent',
                    'remote_message_id' => $remoteMessageId,
                    'body' => $message['body'] ?? '',
                ]);
            }
        } catch (Throwable $e) {
            Log::warning("[Helpdesk] Pobranie zgłoszenia #{$ticket->id} nie powiodło się: ".$e->getMessage());
        }
    }

    private function request()
    {
        return Http::timeout((int) config('helpdesk.timeout', 5))
            ->acceptJson()
            ->asJson();
    }

    private function apiRequest()
    {
        return $this->request()->withToken(config('helpdesk.api_token'));
    }

    private function endpoint(string $action): string
    {
        return config('helpdesk.url')."/api/v1/licenses/{$action}";
    }

    private function apiEndpoint(string $path): string
    {
        return config('helpdesk.url')."/api/v1/{$path}";
    }
}
