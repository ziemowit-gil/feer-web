<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Spis treści „Na tej stronie" budowany z nagłówków H2/H3 wyrenderowanej treści.
 *
 * Nagłówki bez atrybutu `id` dostają go (slug z tekstu, unikalny w obrębie
 * treści), dzięki czemu spis i linki głębokie (…#sekcja) działają bez zmian
 * w edytorze. Nagłówki, które już mają `id`, zachowują je — dzięki temu zapis
 * z edycji „na żywo" (TinyMCE zachowuje id) nie mnoży identyfikatorów.
 */
final class TableOfContents
{
    /** Minimalna liczba nagłówków, od której spis ma sens. */
    public const MIN_ITEMS = 3;

    /**
     * @return array{0: string, 1: array<int, array{id: string, text: string, level: int}>}
     *         HTML z wstrzykniętymi id oraz lista pozycji (pusta, gdy nagłówków jest mniej niż $min).
     */
    public static function inject(?string $html, int $min = self::MIN_ITEMS): array
    {
        $html = (string) $html;

        if ($html === '' || stripos($html, '<h2') === false && stripos($html, '<h3') === false) {
            return [$html, []];
        }

        $items = [];
        $used = [];

        $result = preg_replace_callback(
            '/<h([23])\b([^>]*)>(.*?)<\/h\1\s*>/is',
            function (array $m) use (&$items, &$used): string {
                $level = (int) $m[1];
                $attrs = $m[2];
                $inner = $m[3];
                $text = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                if ($text === '') {
                    return $m[0];
                }

                if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                    $id = $idMatch[1];
                } else {
                    $base = Str::slug(Str::limit($text, 80, '')) ?: 'sekcja';
                    $id = $base;
                    $n = 2;
                    while (isset($used[$id])) {
                        $id = $base . '-' . $n++;
                    }
                    $attrs .= ' id="' . e($id) . '"';
                }

                $used[$id] = true;
                $items[] = ['id' => $id, 'text' => $text, 'level' => $level];

                return "<h{$level}{$attrs}>{$inner}</h{$level}>";
            },
            $html,
        );

        if ($result === null) {
            return [$html, []];
        }

        // Spis tylko z samych H3 (bez H2) też jest w porządku, ale poniżej progu nie ma sensu.
        return [$result, count($items) >= $min ? $items : []];
    }
}
