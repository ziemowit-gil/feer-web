<?php

namespace App\Models\Concerns;

use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\URL;

/**
 * Adresy publiczne rekordu liczone według witryny, do której rekord należy, a nie według adresu, pod którym otwarty
 * jest panel. Dzięki temu „Zobacz na stronie” i podgląd wersji roboczej działają także dla treści witryn w sieci
 * (własna domena albo prefiks ścieżki), zamiast kończyć się błędem 404 na witrynie głównej.
 *
 * Adres podpisany liczymy z tym samym korzeniem (domena albo adres główny z prefiksem), który zobaczy żądanie po
 * wejściu w link (patrz Middleware\ResolveCurrentSite), więc podpis się zgadza.
 */
trait BuildsSitePublicUrls
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function siteAwareRoute(string $name, array $parameters, ?CarbonInterface $signedUntil = null): string
    {
        $site = $this->getAttribute('site_id') ? SiteSetting::query()->find($this->getAttribute('site_id')) : null;
        $root = ($site && $site->parent_site_id) ? current_site_url($site) : null;

        if ($root === null) {
            return $this->makeUrl($name, $parameters, $signedUntil);
        }

        $previous = \Closure::bind(fn () => $this->forcedRoot, app('url'), UrlGenerator::class)();
        URL::forceRootUrl($root);

        try {
            return $this->makeUrl($name, $parameters, $signedUntil);
        } finally {
            URL::forceRootUrl($previous);
        }
    }

    private function makeUrl(string $name, array $parameters, ?CarbonInterface $signedUntil): string
    {
        return $signedUntil
            ? URL::temporarySignedRoute($name, $signedUntil, $parameters)
            : route($name, $parameters);
    }
}
