<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klient API GetResponse v3 (nagłówek `X-Auth-Token: api-key …`): kontakty z paginacją
 * stronami (nagłówki TotalCount/TotalPages), listy („kampanie”) i czarna lista konta.
 * Klucz z panelu (zaszyfrowany w SiteSetting) albo z .env (GETRESPONSE_API_KEY).
 */
class GetResponseClient
{
    public function __construct(private readonly string $apiKey, private readonly ?string $baseUrl = null) {}

    public static function token(?SiteSetting $site = null): ?string
    {
        $site ??= SiteSetting::current();
        $token = $site->newsletter_getresponse_token ?: config('services.getresponse.token');

        return filled($token) ? (string) $token : null;
    }

    public static function fromSettings(?SiteSetting $site = null): ?self
    {
        $token = static::token($site);

        return $token ? new self($token) : null;
    }

    /** Liczba kontaktów (opcjonalnie na jednej liście). */
    public function count(?string $campaignId = null): int
    {
        $response = $this->get('contacts', ['perPage' => 1, 'page' => 1] + ($campaignId ? ['query' => ['campaignId' => $campaignId]] : []));

        return (int) ($response->header('TotalCount') ?: count((array) $response->json()));
    }

    /** @return array<int, array{id: string, name: string, total: int}> listy z liczbą kontaktów */
    public function campaigns(): array
    {
        $out = [];
        $page = 1;
        do {
            $response = $this->get('campaigns', ['perPage' => 100, 'page' => $page]);
            foreach ((array) $response->json() as $c) {
                $id = (string) $c['campaignId'];
                $out[] = ['id' => $id, 'name' => (string) ($c['name'] ?? $id), 'total' => $this->count($id)];
            }
            $pages = (int) ($response->header('TotalPages') ?: 1);
        } while ($page++ < $pages && $page <= 20);

        return $out;
    }

    /**
     * Jedna strona kontaktów (stała kolejność po dacie dodania).
     *
     * @return array{data: array<int, array>, more: bool}
     */
    public function contactsPage(int $page, int $perPage = 500): array
    {
        $response = $this->get('contacts', [
            'page' => $page, 'perPage' => $perPage, 'sort' => ['createdOn' => 'ASC'],
            'fields' => 'contactId,email,name,createdOn,ipAddress,campaign',
        ]);
        $data = (array) $response->json();

        return ['data' => $data, 'more' => $data !== [] && $page < (int) ($response->header('TotalPages') ?: $page)];
    }

    /** @return array<int, string> adresy z czarnej listy konta (bez masek domen „@domena”) */
    public function blocklist(): array
    {
        $json = (array) $this->get('accounts/blocklists')->json();
        $masks = $json['masks'] ?? (array_is_list($json) ? $json : []);

        return array_values(array_filter(array_map('strval', $masks), fn ($m) => filter_var($m, FILTER_VALIDATE_EMAIL)));
    }

    private function get(string $path, array $query = []): Response
    {
        try {
            $response = $this->request()->get(ltrim($path, '/'), $query);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Nie udało się połączyć z GetResponse: '.$e->getMessage(), 0, $e);
        } catch (RequestException $e) {
            $response = $e->response;
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new RuntimeException('GetResponse odrzucił klucz API ('.$response->status().'). Sprawdź klucz w GetResponse → Narzędzia → Integracje i API → API.');
        }
        if ($response->failed()) {
            throw new RuntimeException('GetResponse zwrócił błąd '.$response->status().': '.mb_substr((string) ($response->json('message') ?? $response->body()), 0, 200));
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl ?? (string) config('services.getresponse.url', 'https://api.getresponse.com/v3'), '/').'/')
            ->withHeaders(['X-Auth-Token' => 'api-key '.$this->apiKey])
            ->acceptJson()
            ->timeout(30)
            // Limity API GetResponse — przy 429 i zerwanym połączeniu ponawiamy.
            ->retry(3, 2000, fn ($e) => $e instanceof ConnectionException || ($e instanceof RequestException && $e->response->status() === 429), throw: false);
    }
}
