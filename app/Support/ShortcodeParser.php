<?php

namespace App\Support;

use App\Models\FormDefinition;
use Illuminate\Support\Facades\View;

/**
 * Parsuje shortcody w treści WYSIWYG i zastępuje je wyrenderowanym HTML.
 *
 * Obsługiwane shortcody:
 *   [formularz:slug] — osadza formularz o podanym identyfikatorze
 */
class ShortcodeParser
{
    /** Parsuje shortcody w $content i zwraca gotowy HTML. */
    public static function render(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        return preg_replace_callback(
            '/\[formularz:([a-z0-9_\-]+)\]/i',
            fn ($matches) => static::renderForm(trim($matches[1])),
            $content,
        );
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
}
