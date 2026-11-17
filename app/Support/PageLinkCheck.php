<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sprawdzanie linków jednej strony — do karty strony w Strony Nawigator (jak „Linkvalidator” w TYPO3 dla jednej strony).
 * Te same reguły co skaner całej witryny (LinkCheckController): link wewnętrzny /slug musi prowadzić do opublikowanej
 * strony, zewnętrzny nie może zwracać błędu HTTP (405 na HEAD jest pomijane). Wynik w pamięci podręcznej przy stronie.
 */
class PageLinkCheck
{
    public const MAX_EXTERNAL = 40;

    /** @return array<int, string> unikalne adresy z treści strony (bez kotwic, mailto:, tel: …) */
    public static function links(Page $page): array
    {
        preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', (string) $page->content, $m);

        return array_values(array_unique(array_filter(array_map('trim', $m[1]),
            fn ($url) => $url !== '' && ! Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:', 'data:']))));
    }

    /**
     * Sprawdza linki strony i zapisuje wynik.
     *
     * @return array{checked:int, broken: array<int, array{url:string, error:string}>, at:string, capped:bool}
     */
    public static function run(Page $page): array
    {
        $links = self::links($page);
        $broken = [];

        foreach ($links as $url) {
            if (Str::startsWith($url, '/') && ! Str::startsWith($url, '//')) {
                $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
                if ($path === '' || str_contains($path, '/')) {
                    continue; // strona główna albo trasa wieloczłonowa — jak w skanerze całej witryny
                }
                if (! Page::where('slug', $path)->where('is_published', true)->exists() && ! in_array($path, Page::RESERVED_SLUGS ?? [], true)) {
                    $broken[] = ['url' => $url, 'error' => 'Brak opublikowanej strony /'.$path];
                }
            }
        }

        $external = array_slice(array_values(array_filter($links, fn ($u) => Str::startsWith($u, ['http://', 'https://']))), 0, self::MAX_EXTERNAL);
        if ($external !== []) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn ($url) => $pool->as($url)->connectTimeout(5)->timeout(8)->withHeaders(['User-Agent' => 'FEER-LinkChecker/1.0'])->head($url),
                $external,
            ));
            foreach ($external as $url) {
                $res = $responses[$url] ?? null;
                if ($res instanceof \Throwable) {
                    $broken[] = ['url' => $url, 'error' => 'Brak połączenia albo błędny adres'];
                } elseif ($res && $res->status() >= 400 && $res->status() !== 405) {
                    $broken[] = ['url' => $url, 'error' => 'Odpowiedź serwera '.$res->status()];
                }
            }
        }

        $result = ['checked' => count($links), 'broken' => $broken, 'at' => now()->toDateTimeString(),
            'capped' => count(array_filter($links, fn ($u) => Str::startsWith($u, ['http://', 'https://']))) > self::MAX_EXTERNAL,
            'content_hash' => md5((string) $page->content)];
        Cache::put(self::key($page), $result, now()->addDays(30));

        return $result;
    }

    /**
     * Ostatni wynik dla strony: własne sprawdzenie (jeśli treść się od tego czasu nie zmieniła), a bez niego —
     * pozycje tej strony z ostatniego skanu całej witryny.
     *
     * @return array{source:string, checked:?int, broken: array<int, array{url:string, error:string}>, at:?string, stale:bool}|null
     */
    public static function last(Page $page): ?array
    {
        $own = Cache::get(self::key($page));
        if (is_array($own)) {
            return ['source' => 'page', 'checked' => $own['checked'], 'broken' => $own['broken'], 'at' => $own['at'],
                'stale' => ($own['content_hash'] ?? null) !== md5((string) $page->content)];
        }

        $at = Cache::get('link_check.scanned_at');
        if (! $at) {
            return null;
        }
        $edit = route('admin.podstrony.edit', $page);
        $broken = array_values(array_map(fn ($r) => ['url' => $r['url'], 'error' => $r['error'] ?? ''],
            array_filter(Cache::get('link_check.results', []), fn ($r) => ($r['edit_url'] ?? null) === $edit)));

        return ['source' => 'site', 'checked' => null, 'broken' => $broken, 'at' => $at, 'stale' => false];
    }

    private static function key(Page $page): string
    {
        return 'link_check.page.'.$page->id;
    }
}
