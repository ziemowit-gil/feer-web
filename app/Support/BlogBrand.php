<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Nazwa i opisy bloga (moduł Blog) w jednym miejscu. Domyślnie „Wiem FEER”; inna instalacja (np. strona Kreatywnych)
 * ustawia je w .env: BLOG_NAME, BLOG_INTRO, BLOG_DESCRIPTION (`:site` = nazwa witryny), BLOG_PREFIX (adres, np. „blog”).
 * Gdy blog działa jako osobna witryna sieci (BindBlogSite, atrybut `blog_site`), na stronie publicznej nazwą jest
 * nazwa tej witryny.
 */
final class BlogBrand
{
    public const DEFAULT_NAME = 'Wiem FEER';

    /** Nazwa bloga w panelu i w tekstach (bez nazwy witryny). */
    public static function name(): string
    {
        return trim((string) config('blog.name', '')) ?: self::DEFAULT_NAME;
    }

    /** Nazwa na stronie publicznej: witryna bloga albo nazwa z konfiguracji. */
    public static function publicName(): string
    {
        return request()->attributes->get('blog_site') ? (string) SiteSetting::current()->site_name : self::name();
    }

    /** Czy to domyślny blog FEER (teksty o dostępności, krój odręczny w nagłówku). */
    public static function isDefault(): bool
    {
        return self::name() === self::DEFAULT_NAME;
    }

    /** Wstęp pod nagłówkiem listy wpisów. */
    public static function intro(): string
    {
        $intro = trim((string) config('blog.intro', ''));

        return $intro !== '' ? $intro : (self::isDefault()
            ? 'Artykuły o dostępności cyfrowej, edukacji i empatii. To miejsce, w którym dzielimy się wiedzą i doświadczeniem.'
            : 'Najnowsze wpisy, relacje i porady. To miejsce, w którym dzielimy się tym, co u nas słychać.');
    }

    /** Opis listy wpisów dla wyszukiwarek. */
    public static function description(): string
    {
        $site = (string) SiteSetting::current()->site_name;
        $description = trim((string) config('blog.description', ''));
        if ($description !== '') {
            return str_replace(':site', $site, $description);
        }

        return self::isDefault()
            ? self::DEFAULT_NAME.' — blog fundacji '.$site.': artykuły o dostępności, edukacji i empatii.'
            : self::name().' — '.$site.': najnowsze wpisy, relacje i porady.';
    }
}
