<?php

namespace App\Support;

use App\Models\FormDefinition;
use App\Models\GdprClause;
use App\Models\Page;
use Illuminate\Support\Facades\View;

/**
 * Parsuje shortcody w treści WYSIWYG i zastępuje je wyrenderowanym HTML.
 *
 * Obsługiwane shortcody:
 *   [formularz:slug] — osadza formularz o podanym identyfikatorze
 *   [kafelki:slug]   — osadza siatkę kafelków ze strony typu tiles_grid
 *   [pasek:ID]       — wstawia pasek z modułu „FEER Paski” (tytuł, tekst, przyciski)
 *   [klauzule-rodo]  — lista klauzul informacyjnych RODO zaimportowanych z SZO
 *                      ([klauzule-rodo:en] — wersje angielskie)
 */
class ShortcodeParser
{
    /** Parsuje shortcody w $content i zwraca gotowy HTML. */
    public static function render(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        $content = preg_replace_callback(
            '/\[formularz:([a-z0-9_\-]+)\]/i',
            fn ($matches) => static::renderForm(trim($matches[1])),
            $content,
        );

        $content = preg_replace_callback(
            '/\[kafelki:([a-z0-9_\-]+)\]/i',
            fn ($matches) => static::renderTilesGrid(trim($matches[1])),
            $content,
        );

        $content = preg_replace_callback(
            '/(?:<p>\s*)?\[pasek:(\d+)\](?:\s*<\/p>)?/i',
            fn ($matches) => static::renderBand((int) $matches[1]),
            $content,
        );

        $content = preg_replace_callback(
            '/(?:<p>\s*)?\[klauzule-rodo(?::([a-z]{2}))?\](?:\s*<\/p>)?/i',
            fn ($matches) => static::renderGdprClauses(strtolower($matches[1] ?? '') ?: 'pl'),
            $content,
        );

        return $content;
    }

    /** Wzorzec wszystkich shortcodów — do wykrywania (np. wyłączenie edycji inline). */
    public const DETECT_PATTERN = '/\[(?:(?:formularz|kafelki):[a-z0-9_\-]+|pasek:\d+|klauzule-rodo(?::[a-z]{2})?)\]/i';

    public static function has(?string $content): bool
    {
        return filled($content) && (bool) preg_match(self::DETECT_PATTERN, $content);
    }

    private static function renderBand(int $id): string
    {
        $site = \App\Models\SiteSetting::current();
        if (! $site->isModuleEnabled('feer_bands')) {
            return '';
        }

        $band = \App\Models\FeerBand::forCurrentSite()->active()->find($id);

        return $band ? View::make('partials.feer-band', ['band' => $band, 'inContent' => true])->render() : '';
    }

    private static function renderGdprClauses(string $lang): string
    {
        $clauses = GdprClause::listed($lang)->get();

        return View::make('partials._gdpr-clauses', ['clauses' => $clauses, 'lang' => $lang])->render();
    }

    private static function renderForm(string $slug): string
    {
        static $cache = [];

        if (! isset($cache[$slug])) {
            $cache[$slug] = FormDefinition::where('slug', $slug)
                ->where('is_active', true)
                ->first();
        }

        $form = $cache[$slug];

        if (! $form) {
            return '';
        }

        return View::make('formularz._embed', ['form' => $form])->render();
    }

    private static function renderTilesGrid(string $slug): string
    {
        static $cache = [];

        if (! isset($cache[$slug])) {
            $cache[$slug] = Page::where('slug', $slug)
                ->where('type', 'tiles_grid')
                ->where('is_published', true)
                ->first();
        }

        $page = $cache[$slug];

        if (! $page) {
            return '';
        }

        $tiles = collect($page->tiles ?? [])
            ->filter(fn ($t) => filled($t['label'] ?? null) && filled($t['url'] ?? null))
            ->values();

        if ($tiles->isEmpty()) {
            return '';
        }

        return View::make('partials._tiles-grid', ['tiles' => $tiles, 'label' => $page->title])->render();
    }
}
