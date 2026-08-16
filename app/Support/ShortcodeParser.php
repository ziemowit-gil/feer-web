<?php

namespace App\Support;

use App\Models\FormDefinition;
use App\Models\Page;
use Illuminate\Support\Facades\View;

/**
 * Parsuje shortcody w treści WYSIWYG i zastępuje je wyrenderowanym HTML.
 *
 * Obsługiwane shortcody:
 *   [formularz:slug] — osadza formularz o podanym identyfikatorze
 *   [kafelki:slug]   — osadza siatkę kafelków ze strony typu tiles_grid
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

        return $content;
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
