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

        $content = static::unwrapBlockShortcodes($content);

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
            '/(?:<p>\s*)?\[blok:(\d+)\](?:\s*<\/p>)?/i',
            fn ($matches) => static::renderBlock((int) $matches[1]),
            $content,
        );

        $content = preg_replace_callback(
            '/(?:<p>\s*)?\[kafelki-zestaw:(\d+)\](?:\s*<\/p>)?/i',
            fn ($matches) => static::renderTileSet((int) $matches[1]),
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

    /**
     * Shortcody bloków (kafelki, zestawy, bloki CTA/akordeon, formularze) wstawione przez pomyłkę do ramki
     * („Ważne", notatka, ramka tekstowa, cytat) dziedziczyłyby jej tło i obramowanie — takie ramki rozpakowujemy.
     */
    private static function unwrapBlockShortcodes(string $content): string
    {
        $blocks = '(?:kafelki-zestaw|kafelki|blok|formularz):[a-z0-9_\-]+';

        return preg_replace_callback(
            '~<(blockquote|div class="content-(?:box|note|important)")[^>]*>((?:(?!</?(?:blockquote|div)\b).)*?)</(?:blockquote|div)>~is',
            fn ($m) => preg_match('/\['.$blocks.'\]/i', $m[2]) ? $m[2] : $m[0],
            $content,
        ) ?? $content;
    }

    /** Wzorzec wszystkich shortcodów — do wykrywania (np. wyłączenie edycji inline). */
    public const DETECT_PATTERN = '/\[(?:(?:formularz|kafelki):[a-z0-9_\-]+|kafelki-zestaw:\d+|blok:\d+|pasek:\d+|klauzule-rodo(?::[a-z]{2})?)\]/i';

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

    /** Blok treści z kreatora edytora ([blok:ID]): przyciski CTA albo akordeon. */
    private static function renderBlock(int $id): string
    {
        $block = \App\Models\ContentBlock::find($id);
        if (! $block) {
            return '';
        }

        return View::make('partials.blocks.'.($block->type === 'accordion' ? 'accordion' : 'cta'), ['block' => $block])->render();
    }

    /** Zestaw kafelków z kreatora edytora ([kafelki-zestaw:ID]). */
    private static function renderTileSet(int $id): string
    {
        $set = \App\Models\TileSet::find($id);
        if (! $set) {
            return '';
        }

        $html = '';
        foreach (TileSections::groups($set->tiles) as $g) {
            $hid = $g['heading'] ? 'tiles-h-'.\Illuminate\Support\Str::random(8) : null;
            $html .= '<section'.($hid ? ' aria-labelledby="'.$hid.'"' : '').'>';
            if ($hid) {
                $html .= '<h2 id="'.$hid.'" class="mb-3 mt-8 text-2xl font-bold text-ink">'.e($g['heading']).'</h2>';
            }
            $html .= View::make('partials._tiles-grid', ['tiles' => collect($g['tiles']), 'label' => $set->name, 'labelledby' => $hid])->render().'</section>';
        }

        return $html;
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
