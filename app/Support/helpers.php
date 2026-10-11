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

if (! function_exists('site_home_url')) {
    /**
     * Adres „strony głównej" bieżącej witryny dla logo w nagłówku. Na witrynie
     * bloga Wiem FEER (middleware blog.site ustawia atrybut `blog_site`) logo
     * prowadzi do strony głównej bloga (/wiem), a nie do witryny fundacji.
     */
    function site_home_url(): string
    {
        if (request()?->attributes->get('blog_site') && \Illuminate\Support\Facades\Route::has('blog.index')) {
            return route('blog.index');
        }

        return site_route('home');
    }
}


if (! function_exists('meta_excerpt')) {
    /**
     * Opis strony (meta description) z treści HTML: usuwa shortcode'y (np. „[nabory]",
     * „[formularz:kontakt]"), znaczniki (ze spacją między blokami, żeby słowa się nie sklejały),
     * dekoduje encje, zwija białe znaki i przycina do $limit znaków.
     */
    function meta_excerpt(?string $html, int $limit = 160): string
    {
        $text = (string) $html;
        if (trim($text) === '') {
            return '';
        }

        $text = preg_replace(\App\Support\ShortcodeParser::DETECT_PATTERN, ' ', $text) ?? $text;
        $text = preg_replace('/\[[a-z0-9_-]+(?::[^\]]*)?\]/i', ' ', $text) ?? $text;
        // Skrypty i style nie są treścią — wycinamy razem z zawartością.
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $text) ?? $text;
        $text = strip_tags(str_replace('<', ' <', $text));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return \Illuminate\Support\Str::limit($text, $limit);
    }
}
