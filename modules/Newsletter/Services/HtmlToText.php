<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

/** Prosta konwersja HTML → tekst dla wersji text/plain i wariantów push/SMS. */
class HtmlToText
{
    public static function convert(string $html): string
    {
        $html = preg_replace('/<(script|style|head)\b.*?<\/\1>/is', '', $html) ?? '';
        $html = preg_replace('/<a\b[^>]*href\s*=\s*("|\')([^"\']+)\1[^>]*>(.*?)<\/a>/is', '$3 ($2)', $html) ?? '';
        $html = preg_replace('/<(br|\/p|\/div|\/tr|\/h[1-6]|\/li)\b[^>]*>/i', "\n", $html) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? '';
        $text = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $text) ?? '';

        return trim($text);
    }
}
