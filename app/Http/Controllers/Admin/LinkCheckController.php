<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Panel admin: skaner martwych linków w treści aktualności, podstron i projektów.
 *
 * Metody: index(), scan().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class LinkCheckController extends Controller
{
    /** Maks. liczba zewnętrznych adresów sprawdzanych w jednym skanie. */
    private const MAX_EXTERNAL = 150;

    /** Wyświetla wyniki ostatniego skanowania martwych linków. */
    public function index()
    {
        return view('admin.link-check.index', [
            'results' => Cache::get('link_check.results', []),
            'scannedAt' => Cache::get('link_check.scanned_at'),
            'capped' => Cache::get('link_check.capped', false),
        ]);
    }

    /** Uruchamia skanowanie linków w treściach i zapisuje wyniki w cache. */
    public function scan()
    {
        $links = $this->collectLinks();          // ['url' => ['label'=>, 'edit_url'=>], ...]
        $broken = [];

        // 1) Linki wewnętrzne /{slug} — sprawdzamy istnienie opublikowanej strony
        //    (bez odpytywania własnego serwera, które przy artisan serve zawiesza).
        $internal = array_filter($links, fn ($v, $url) => Str::startsWith($url, '/') && ! Str::startsWith($url, '//'), ARRAY_FILTER_USE_BOTH);
        foreach ($internal as $url => $meta) {
            $path = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            if ($path === '' || str_contains($path, '/')) {
                continue; // strona główna lub trasa wieloczłonowa — pomijamy
            }
            if (! Page::where('slug', $path)->where('is_published', true)->exists()
                && ! in_array($path, Page::RESERVED_SLUGS ?? [], true)) {
                $broken[] = $meta + ['url' => $url, 'status' => null, 'error' => 'Brak opublikowanej strony /'.$path];
            }
        }

        // 2) Linki zewnętrzne — równoległe zapytania HEAD.
        $external = array_filter($links, fn ($url) => Str::startsWith($url, ['http://', 'https://']), ARRAY_FILTER_USE_KEY);
        $external = array_slice($external, 0, self::MAX_EXTERNAL, true);
        $urls = array_keys($external);

        if ($urls !== []) {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn ($url) => $pool->as($url)->connectTimeout(5)->timeout(8)->withHeaders(['User-Agent' => 'FEER-LinkChecker/1.0'])->head($url),
                $urls,
            ));

            foreach ($urls as $url) {
                $res = $responses[$url] ?? null;
                if ($res instanceof \Throwable) {
                    $broken[] = $external[$url] + ['url' => $url, 'status' => null, 'error' => 'Brak połączenia / błędny adres'];
                } elseif ($res && $res->status() >= 400 && $res->status() !== 405) {
                    $broken[] = $external[$url] + ['url' => $url, 'status' => $res->status(), 'error' => 'Odpowiedź HTTP '.$res->status()];
                }
            }
        }

        Cache::forever('link_check.results', $broken);
        Cache::forever('link_check.scanned_at', now()->toDateTimeString());
        Cache::forever('link_check.capped', count($external) >= self::MAX_EXTERNAL);

        return redirect()->route('admin.martwe-linki.index')
            ->with('status', 'Skanowanie zakończone. Sprawdzono '.count($links).' linków, znaleziono '.count($broken).' problemów.');
    }

    /**
     * Zbiera linki z treści aktualności, stron i projektów.
     * Zwraca [url => ['label' => źródło, 'edit_url' => edycja]] (pierwsze wystąpienie).
     *
     * @return array<string, array{label:string, edit_url:string}>
     */
    private function collectLinks(): array
    {
        $links = [];

        $sources = [
            [News::query(), 'admin.newsy.edit', 'Aktualność', ['content']],
            [Page::query(), 'admin.podstrony.edit', 'Strona', ['content']],
            [Project::query(), 'admin.projekty.edit', 'Projekt', ['content', 'why', 'outcomes']],
        ];

        foreach ($sources as [$query, $editRoute, $typeLabel, $fields]) {
            foreach ($query->get() as $model) {
                $html = implode(' ', array_map(fn ($f) => (string) ($model->{$f} ?? ''), $fields));
                preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)["\']/i', $html, $matches);

                foreach ($matches[1] as $url) {
                    $url = trim($url);
                    if ($url === '' || Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:', 'data:'])) {
                        continue;
                    }
                    $links[$url] ??= [
                        'label' => $typeLabel.': '.$model->title,
                        'edit_url' => route($editRoute, $model),
                    ];
                }
            }
        }

        return $links;
    }
}
