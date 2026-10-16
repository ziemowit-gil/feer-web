<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klient nowego API MailerLite (connect.mailerlite.com, token Bearer): subskrybenci
 * z paginacją kursorową, grupy i pola. Token z panelu (zaszyfrowany w SiteSetting)
 * albo z .env (MAILERLITE_API_TOKEN).
 */
class MailerLiteClient
{
    /** Statusy subskrybenta w MailerLite. */
    public const STATUSES = ['active', 'unsubscribed', 'unconfirmed', 'bounced', 'junk'];

    public function __construct(private readonly string $token, private readonly ?string $baseUrl = null) {}

    public static function token(?SiteSetting $site = null): ?string
    {
        $site ??= SiteSetting::current();
        $token = $site->newsletter_mailerlite_token ?: config('services.mailerlite.token');

        return filled($token) ? (string) $token : null;
    }

    public static function fromSettings(?SiteSetting $site = null): ?self
    {
        $token = static::token($site);

        return $token ? new self($token) : null;
    }

    /** Liczba subskrybentów (opcjonalnie o danym statusie) — `limit=0` zwraca samą sumę. */
    public function count(?string $status = null): int
    {
        $json = $this->get('subscribers', ['limit' => 0] + ($status ? ['filter' => ['status' => $status]] : []));

        return (int) ($json['total'] ?? data_get($json, 'meta.total', 0));
    }

    /** @return array<string, int> status => liczba */
    public function countsByStatus(): array
    {
        $out = [];
        foreach (self::STATUSES as $status) {
            $out[$status] = $this->count($status);
        }

        return $out;
    }

    /**
     * Jedna strona subskrybentów (wszystkie statusy) razem z grupami.
     *
     * @return array{data: array<int, array>, next_cursor: ?string}
     */
    public function subscribersPage(?string $cursor = null, int $limit = 500): array
    {
        $json = $this->get('subscribers', array_filter(['limit' => $limit, 'cursor' => $cursor, 'include' => 'groups']));

        return [
            'data' => $json['data'] ?? [],
            'next_cursor' => data_get($json, 'meta.next_cursor') ?: null,
        ];
    }

    /** @return array<int, array{id: string, name: string, active_count: int, total: int}> */
    public function groups(): array
    {
        $out = [];
        $page = 1;
        do {
            $json = $this->get('groups', ['limit' => 100, 'page' => $page]);
            foreach ($json['data'] ?? [] as $g) {
                $out[] = [
                    'id' => (string) $g['id'],
                    'name' => (string) ($g['name'] ?? $g['id']),
                    'active_count' => (int) ($g['active_count'] ?? 0),
                    'total' => (int) (($g['active_count'] ?? 0) + ($g['unsubscribed_count'] ?? 0) + ($g['unconfirmed_count'] ?? 0) + ($g['bounced_count'] ?? 0) + ($g['junk_count'] ?? 0)),
                ];
            }
            $last = (int) data_get($json, 'meta.last_page', $page);
        } while ($page++ < $last && $page <= 100);

        return $out;
    }

    /** @return array<string, string> klucz pola => nazwa */
    public function fields(): array
    {
        $json = $this->get('fields', ['limit' => 100]);
        $out = [];
        foreach ($json['data'] ?? [] as $f) {
            $out[(string) $f['key']] = (string) ($f['name'] ?? $f['key']);
        }

        return $out;
    }

    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->request()->get(ltrim($path, '/'), $query);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Nie udało się połączyć z MailerLite: '.$e->getMessage(), 0, $e);
        } catch (RequestException $e) {
            $response = $e->response;
        }

        if ($response->status() === 401) {
            throw new RuntimeException('MailerLite odrzucił token API (401). Sprawdź token w panelu MailerLite → Integracje → API.');
        }
        if ($response->failed()) {
            throw new RuntimeException('MailerLite zwrócił błąd '.$response->status().': '.mb_substr((string) ($response->json('message') ?? $response->body()), 0, 200));
        }

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl ?? (string) config('services.mailerlite.url', 'https://connect.mailerlite.com/api'), '/').'/')
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(30)
            // Limit API to 120 zapytań/min — przy 429 i zerwanym połączeniu ponawiamy.
            ->retry(3, 2000, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->status() === 429), throw: false);
    }
}
