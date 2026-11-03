<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use App\Models\SiteAddressRedirect;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jedyne miejsce, które rozpoznaje witrynę (główną albo podrzędną) dla żądania publicznego. Witryna jest dostępna
 * w dwóch postaciach adresu:
 *
 *   1. pod własną domeną (`domain` w ustawieniach witryny), np. https://szkolenia.feer.org.pl/aktualnosci,
 *   2. pod prefiksem ścieżki witryny głównej: https://feer.org.pl/szkolenia/aktualnosci.
 *
 * W obu przypadkach to te same trasy co na witrynie głównej: przy prefiksie pierwszy segment ścieżki jest „zdejmowany”
 * jak katalog instalacji (baseUrl żądania), więc router widzi /aktualnosci, a wszystkie adresy generowane w tym
 * żądaniu (route(), url(), redirect(), adresy podpisane) same dostają prefiks. Nie ma osobnych tras „site.*”,
 * podwójnych adresów /site/{slug}/… ani helpera, który trzeba pamiętać (site_route() to zwykłe route()).
 *
 * Stary adres /site/{slug}/… przekierowuje na /{slug}/…. Slug bloga (config blog.site_slug) i slugi zarezerwowane
 * na trasy witryny głównej nigdy nie są prefiksem witryny.
 *
 * Dawny adres subwitryny po zmianie nazwy i adresu (adres skrócony albo domena, patrz SiteAddressRedirect)
 * przekierowuje na jej obecny adres: 301 ze ścieżką i zapytaniem (308 dla metod innych niż GET i HEAD, żeby zachować
 * treść żądania). Żywy adres zawsze wygrywa — przekierowanie sprawdzamy dopiero, gdy żadna witryna nie ma takiej domeny
 * ani takiego prefiksu, a adres zajęty przez istniejącą stronę, panel albo blog nigdy nie jest przekierowaniem.
 *
 * Panel (/edycja) nie przechodzi tędy: tam witrynę wybiera sesja (ResolveAdminActiveSite).
 */
class ResolveCurrentSite
{
    public const SLUG_MAP_CACHE_KEY = 'site_slug_map';

    /** Korzeń adresów przed zamontowaniem prefiksu (null = brak wymuszonego), do przywrócenia po żądaniu. */
    private static string|false|null $originalRoot = false;

    /** Czy witrynę związał ten middleware (wtedy zwalniamy ją po żądaniu). */
    private static bool $boundHere = false;

    public function handle(Request $request, Closure $next): Response
    {
        $alreadyBound = app()->bound(SiteSetting::CURRENT_SITE_BINDING);

        $domainSite = $alreadyBound
            ? null
            : SiteSetting::query()->notTrashed()->whereNotNull('domain')->where('domain', $request->getHost())->first();

        if ($domainSite) {
            app()->instance(SiteSetting::CURRENT_SITE_BINDING, $domainSite);
            self::$boundHere = true;

            return $next($request);
        }

        // Dawna domena subwitryny: na jej obecny adres, z tą samą ścieżką i zapytaniem.
        if (! $alreadyBound) {
            $moved = $this->redirectFromOldAddress($request, SiteAddressRedirect::TYPE_DOMAIN, $request->getHost(), $request->getPathInfo());

            if ($moved) {
                return $moved;
            }
        }

        $segments = explode('/', trim($request->getPathInfo(), '/'), 3);
        $first = strtolower($segments[0] ?? '');

        if ($first === '') {
            return $next($request);
        }

        // Stary, długi adres: /site/{slug}/… → /{slug}/… (dawny slug od razu na obecny adres, bez pośredniego przeskoku).
        if ($first === 'site' && isset($segments[1]) && $segments[1] !== '') {
            if ($this->siteIdForSlug(strtolower($segments[1])) !== null) {
                $target = '/'.$segments[1].(isset($segments[2]) ? '/'.$segments[2] : '');
                $query = $request->getQueryString();

                return redirect($target.($query ? '?'.$query : ''), 301);
            }

            $moved = $this->redirectFromOldAddress($request, SiteAddressRedirect::TYPE_SLUG, $segments[1], $this->pathAfter($request, 2));

            if ($moved) {
                return $moved;
            }
        }

        $siteId = $this->siteIdForSlug($first);

        if ($siteId === null) {
            return $this->redirectFromOldAddress($request, SiteAddressRedirect::TYPE_SLUG, $first, $this->pathAfter($request, 1))
                ?? $next($request);
        }

        $site = SiteSetting::query()->notTrashed()->find($siteId);

        if (! $site) {
            return $next($request);
        }

        return $next($this->mountAtPrefix($request, $site, $segments[0]));
    }

    /**
     * Przekierowanie ze starego adresu subwitryny (SiteAddressRedirect) na jej obecny adres. Wołane dopiero wtedy, gdy
     * żadna witryna nie ma takiej domeny ani takiego prefiksu, więc żywy adres zawsze ma pierwszeństwo.
     */
    private function redirectFromOldAddress(Request $request, string $type, string $value, string $rest): ?Response
    {
        $siteId = SiteAddressRedirect::siteIdFor($type, $value);

        if ($siteId === null) {
            return null;
        }

        // Adres, pod którym działa już coś innego (panel, API, blog, strona, portal kategorii, adres serwera), nie jest przekierowaniem.
        if (SiteAddressRedirect::shadowReason($type, $value) !== null) {
            return null;
        }

        // Ręczne przekierowanie (Przekierowania w panelu) dla tej ścieżki jest bardziej szczegółowe niż to z dawnego adresu
        // skróconego — obsłuży je HandleRedirects. (Dawna domena: ręczne przekierowania dotyczą ścieżek witryny głównej.)
        if ($type === SiteAddressRedirect::TYPE_SLUG && $request->isMethod('GET') && $this->hasManualRedirect($request)) {
            return null;
        }

        $site = SiteSetting::query()->notTrashed()->find($siteId);

        // Witryna w koszu, usunięta albo bez żadnego adresu nie ma dokąd przekierować — dawny adres zostaje martwy.
        if (! $site || $site->parent_site_id === null || (! $site->domain && ! $site->slug)) {
            return null;
        }

        $target = rtrim(current_site_url($site), '/').$rest;
        $query = (string) $request->getQueryString();

        if ($query !== '') {
            $target .= '?'.$query;
        }

        if ($target === $request->fullUrl()) {
            return null;
        }

        // 308 zamiast 301 zachowuje metodę i treść żądania (np. formularza wysłanego ze starego adresu). Jawny Cache-Control,
        // bo bez niego przeglądarki pamiętają 301 bez końca: przekierowanie da się cofnąć (powrót do dawnego adresu, usunięcie
        // w panelu, zajęcie adresu przez inną witrynę), a zapamiętane w przeglądarce zapętliłoby się albo prowadziło donikąd.
        return redirect()->away($target, in_array($request->getMethod(), ['GET', 'HEAD'], true) ? 301 : 308, ['Cache-Control' => 'no-cache, private']);
    }

    private function hasManualRedirect(Request $request): bool
    {
        try {
            return Schema::hasTable('redirects')
                && Redirect::query()->where('is_active', true)->where('from_path', Redirect::normalizePath($request->path()))->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /** Ścieżka żądania bez pierwszych $segments segmentów, z zachowaniem ukośnika na końcu (/szkolenia/a/ → /a/). */
    private function pathAfter(Request $request, int $segments): string
    {
        return (string) preg_replace('#^(?:/+[^/]*){'.$segments.'}#', '', $request->getPathInfo());
    }

    /** Żądanie widziane przez router bez prefiksu witryny, z adresami generowanymi z prefiksem. */
    private function mountAtPrefix(Request $request, SiteSetting $site, string $prefix): Request
    {
        $root = rtrim(URL::to('/'), '/');
        self::$originalRoot = \Closure::bind(fn () => $this->forcedRoot, app('url'), \Illuminate\Routing\UrlGenerator::class)();

        // Jak aplikacja w podkatalogu: Symfony wylicza baseUrl z SCRIPT_NAME, więc router widzi ścieżkę bez prefiksu,
        // a url()/fullUrl() żądania (i adresy podpisane) zawierają prefiks.
        $server = $request->server->all();
        $server['SCRIPT_NAME'] = '/'.$prefix.'/index.php';
        $server['PHP_SELF'] = '/'.$prefix.'/index.php';
        $server['SCRIPT_FILENAME'] = dirname((string) ($server['SCRIPT_FILENAME'] ?? public_path('index.php'))).'/index.php';
        // Sam prefiks (/szkolenia) traktujemy jak /szkolenia/, inaczej Symfony nie rozpozna go jako katalogu instalacji.
        if (rtrim($request->getPathInfo(), '/') === '/'.$prefix) {
            $query = $request->getQueryString();
            $server['REQUEST_URI'] = '/'.$prefix.'/'.($query ? '?'.$query : '');
        }
        $mounted = $request->duplicate(null, null, null, null, null, $server);
        $mounted->attributes->set('site_path_prefixed', true);
        $mounted->attributes->set('site_slug', $site->slug);

        app()->instance('request', $mounted);
        app()->instance(SiteSetting::CURRENT_SITE_BINDING, $site);
        self::$boundHere = true;

        URL::forceRootUrl($root.'/'.$prefix);
        URL::useAssetOrigin($root);

        return $mounted;
    }

    private function siteIdForSlug(string $slug): ?int
    {
        if (in_array($slug, self::neverPrefix(), true)) {
            return null;
        }

        return $this->slugMap()[$slug] ?? null;
    }

    /**
     * Pierwsze segmenty adresu, które nigdy nie są prefiksem witryny (panel, API, blog…) — ani dawnym adresem witryny.
     *
     * @return list<string>
     */
    public static function neverPrefix(): array
    {
        return array_values(array_unique(array_filter([
            strtolower((string) config('blog.site_slug', 'wiem')),
            strtolower((string) config('app.admin_prefix', 'admin')),
            'admin', 'api', 'storage', 'build', 'up', 'site',
        ])));
    }

    /** @return array<string, int> slug (małymi literami) → id witryny, bez kosza */
    private function slugMap(): array
    {
        try {
            return Cache::remember(self::SLUG_MAP_CACHE_KEY, 300, function () {
                return SiteSetting::query()->notTrashed()->whereNotNull('slug')->where('slug', '!=', '')
                    ->pluck('id', 'slug')
                    ->mapWithKeys(fn ($id, $slug) => [strtolower((string) $slug) => (int) $id])
                    ->all();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Po żądaniu z prefiksem generator adresów wraca do stanu sprzed zamontowania. W zwykłym PHP-FPM proces kończy się
     * razem z żądaniem; ma to znaczenie w testach i procesach długo działających, gdzie stan zostałby w następnym żądaniu.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (self::$boundHere) {
            app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);
            self::$boundHere = false;
        }

        if (self::$originalRoot !== false) {
            URL::forceRootUrl(self::$originalRoot);
            URL::useAssetOrigin(null);
            self::$originalRoot = false;
            app()->instance('request', $request);
        }
    }
}
