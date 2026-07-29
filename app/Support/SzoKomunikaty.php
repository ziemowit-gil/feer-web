<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pobiera komunikaty z systemu SZO (GET {adres}/api/komunikaty/list.php).
 *
 * Zwraca strukturę z flagą `ok`, aby strefa mogła odróżnić „brak komunikatów"
 * (ok = true, pusta lista) od „Błąd połączenia" (ok = false). Udane odpowiedzi
 * są krótko cache'owane, żeby strefa nie odpytywała SZO przy każdym wejściu;
 * błędów nie cache'ujemy, więc po powrocie SZO strefa od razu wraca do normy.
 */
class SzoKomunikaty
{
    /** Możliwe klucze listy komunikatów w odpowiedzi (gdy nie jest to lista wprost). */
    private const LIST_KEYS = ['komunikaty', 'data', 'items', 'list', 'results'];

    /** Możliwe klucze pól pojedynczego komunikatu (kolejność = priorytet). */
    private const TITLE_KEYS = ['tytul', 'title', 'temat', 'naglowek', 'name'];

    private const BODY_KEYS = ['tresc', 'content', 'body', 'text', 'opis', 'message'];

    private const DATE_KEYS = ['data', 'date', 'created_at', 'published_at', 'data_publikacji', 'datetime'];

    private const URL_KEYS = ['url', 'link', 'href'];

    /**
     * @return array{ok: bool, items: array<int, array{title: string, body: string, date: ?Carbon, url: ?string}>}
     */
    public static function fetch(string $url, int $limit = 30): array
    {
        $url = trim($url);

        if ($url === '') {
            return ['ok' => false, 'items' => []];
        }

        $cacheKey = 'szo_komunikaty:'.md5($url).":$limit";

        if (is_array($cached = Cache::get($cacheKey))) {
            return ['ok' => true, 'items' => $cached];
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(8)
                ->retry(1, 200)
                ->withHeaders(['User-Agent' => 'FEER-Strefa/1.0'])
                ->get($url);

            if (! $response->successful()) {
                return ['ok' => false, 'items' => []];
            }

            $json = $response->json();

            if (! is_array($json)) {
                return ['ok' => false, 'items' => []];
            }

            $items = self::normalize($json, $limit);

            Cache::put($cacheKey, $items, now()->addMinutes(5));

            return ['ok' => true, 'items' => $items];
        } catch (\Throwable $e) {
            Log::warning('SZO: pobranie komunikatów nie powiodło się.', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);

            return ['ok' => false, 'items' => []];
        }
    }

    /**
     * Wyłuskuje listę komunikatów z odpowiedzi (lista wprost albo pod znanym
     * kluczem) i normalizuje pola każdego wpisu do stałego kształtu.
     *
     * @param  array<mixed>  $json
     * @return array<int, array{title: string, body: string, date: ?Carbon, url: ?string}>
     */
    private static function normalize(array $json, int $limit): array
    {
        $list = array_is_list($json) ? $json : null;

        if ($list === null) {
            foreach (self::LIST_KEYS as $key) {
                if (isset($json[$key]) && is_array($json[$key])) {
                    $list = array_values($json[$key]);
                    break;
                }
            }
        }

        if (! is_array($list)) {
            return [];
        }

        $items = [];

        foreach ($list as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $title = self::pick($entry, self::TITLE_KEYS);
            $body = self::pick($entry, self::BODY_KEYS);

            if ($title === '' && $body === '') {
                continue;
            }

            $items[] = [
                'title' => $title !== '' ? $title : 'Komunikat',
                'body' => $body,
                'date' => self::parseDate(self::pick($entry, self::DATE_KEYS)),
                'url' => self::pick($entry, self::URL_KEYS) ?: null,
            ];

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /** Pierwsza niepusta wartość spośród podanych kluczy (przycięta). */
    private static function pick(array $entry, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($entry[$key]) && is_scalar($entry[$key]) && trim((string) $entry[$key]) !== '') {
                return trim((string) $entry[$key]);
            }
        }

        return '';
    }

    private static function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
