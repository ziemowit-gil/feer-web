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


if (! function_exists('icon_is_material')) {
    /** Ikona z Google Material Symbols (Outlined): wartość „mi-nazwa", np. „mi-home" albo „mi-arrow_forward". */
    function icon_is_material(?string $value): bool
    {
        return is_string($value) && preg_match('/^mi-[a-z0-9_]+$/', trim($value)) === 1;
    }
}

if (! function_exists('icon_class')) {
    /** Klasa CSS dla ikon fontowych: Font Awesome zostaje, Bootstrap Icons dostaje prefiks „bi" (zapis bez prefiksu też działa). */
    function icon_class(?string $value, string $fallback = 'bi-lightning'): string
    {
        $v = trim((string) $value) ?: $fallback;
        if (preg_match('/(^|\s)fa[srlb]?(-|\s)/', $v) || str_starts_with($v, 'bi ')) {
            return $v;
        }

        return 'bi '.(str_starts_with($v, 'bi-') ? $v : 'bi-'.$v);
    }
}

if (! function_exists('icon_html')) {
    /**
     * Wspólny znacznik ikony dla całego systemu: Material Symbols (mi-…), Font Awesome (fa-…) i Bootstrap Icons (bi-…).
     * Ikona jest dekoracyjna (aria-hidden); styl podaj w $class / $style.
     */
    function icon_html(?string $value, string $class = '', string $style = '', string $fallback = 'bi-lightning'): \Illuminate\Support\HtmlString
    {
        $value = trim((string) $value);
        $attrs = ($style !== '' ? ' style="'.e($style).'"' : '').' aria-hidden="true"';

        if (icon_is_material($value)) {
            return new \Illuminate\Support\HtmlString('<span class="material-symbols-outlined mi-glyph '.e($class).'"'.$attrs.'>'.e(substr($value, 3)).'</span>');
        }

        return new \Illuminate\Support\HtmlString('<i class="'.e(trim(icon_class($value, $fallback).' '.$class)).'"'.$attrs.'></i>');
    }
}
