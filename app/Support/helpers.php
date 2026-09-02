<?php

use App\Models\SiteSetting;

if (! function_exists('site_route')) {
    /**
     * Like route(), but aware of sub-sites reached via a path prefix
     * ("/site/{slug}" or "/{slug}") — inside such a request it generates the
     * prefixed "site.<name>" route with the current siteSlug merged in, so
     * shared partials (nav, news cards, pagination) keep the user inside the
     * sub-site instead of dropping them back onto the main site's URLs.
     *
     * A sub-site reached by its own domain needs no rewriting: its URLs are
     * unprefixed already, so the plain route name is used unchanged.
     */
    function site_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $request = request();

        if ($request && $request->attributes->get('site_path_prefixed')) {
            $siteSlug = $request->attributes->get('site_slug');

            // Strona główna nie ma osobnej nazwanej trasy w krótkiej
            // postaci (patrz Route::missing() w routes/web.php) — budujemy
            // jej adres wprost, żeby linki wewnątrz sub-witryny zawsze
            // zostawały w zamaskowanej, krótkiej formie "/{slug}".
            if ($name === 'home') {
                return $absolute ? url('/'.$siteSlug) : '/'.$siteSlug;
            }

            $parameters = is_array($parameters) ? $parameters : [$parameters];
            $parameters = ['siteSlug' => $siteSlug] + $parameters;

            return route('site.'.$name, $parameters, $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('current_site_url')) {
    /** Public base URL for a given site — its own domain, or the main site's URL with the sub-site's path prefix. */
    function current_site_url(SiteSetting $site): string
    {
        if ($site->domain) {
            return 'https://'.$site->domain;
        }

        if ($site->slug) {
            return url('/'.$site->slug);
        }

        return url('/');
    }
}
