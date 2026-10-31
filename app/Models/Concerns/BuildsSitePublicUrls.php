<?php

namespace App\Models\Concerns;

use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/**
 * Adresy publiczne rekordu liczone według witryny, do której rekord należy, a nie według adresu, pod którym otwarty
 * jest panel. Dzięki temu „Zobacz na stronie” i podgląd wersji roboczej działają także dla treści witryn w sieci
 * (własna domena albo prefiks ścieżki), zamiast kończyć się błędem 404 na witrynie głównej.
 */
trait BuildsSitePublicUrls
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function siteAwareRoute(string $name, array $parameters, ?CarbonInterface $signedUntil = null): string
    {
        $site = $this->getAttribute('site_id') ? SiteSetting::query()->find($this->getAttribute('site_id')) : null;

        if (! $site || ! $site->parent_site_id) {
            return $this->makeUrl($name, $parameters, $signedUntil);
        }

        if (filled($site->domain)) {
            URL::forceRootUrl('https://'.trim((string) $site->domain, '/'));

            try {
                return $this->makeUrl($name, $parameters, $signedUntil);
            } finally {
                URL::forceRootUrl(null);
            }
        }

        if (filled($site->slug) && Route::has('site.'.$name)) {
            return $this->makeUrl('site.'.$name, ['siteSlug' => $site->slug] + $parameters, $signedUntil);
        }

        return $this->makeUrl($name, $parameters, $signedUntil);
    }

    private function makeUrl(string $name, array $parameters, ?CarbonInterface $signedUntil): string
    {
        return $signedUntil
            ? URL::temporarySignedRoute($name, $signedUntil, $parameters)
            : route($name, $parameters);
    }
}
