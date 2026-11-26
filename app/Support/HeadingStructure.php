<?php

namespace App\Support;

/**
 * Automatyczna poprawka struktury nagłówków w HTML treści (WCAG 1.3.1 i 2.4.6).
 *
 * Tytuł strony jest jedynym H1, więc nagłówki treści zaczynają się od H2. Poziomy są przeliczane
 * według zagnieżdżenia (stos rodziców), dzięki czemu znika przeskok (H2 → H4 daje H2 → H3),
 * a dokument z Worda z H1/H2/H3 staje się H2/H3/H4 bez spłaszczania. Puste nagłówki są usuwane.
 * PhpWord zapisuje styl „Tytuł” jako <h0> — traktujemy go jak H1.
 *
 * Ten sam algorytm jest w JS (edytor klasyczny: admin/partials/editor, edytor blokowy: fixHeadingPlan).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HeadingStructure
{
    /**
     * @return array{html: string, changed: int, removed: int}
     */
    public static function fix(string $html, int $top = 2, int $max = 6): array
    {
        $stack = [];   // [poziom oryginalny, poziom nowy]
        $changed = 0;
        $removed = 0;

        $out = preg_replace_callback('/<h([0-6])(\s[^>]*)?>(.*?)<\/h\1\s*>/si', function ($m) use (&$stack, &$changed, &$removed, $top, $max) {
            $inner = $m[3];
            if (trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0") === ''
                && ! preg_match('/<img\b/i', $inner)) {
                $removed++;

                return '';
            }
            $orig = max(1, (int) $m[1]);
            while ($stack && end($stack)[0] >= $orig) {
                array_pop($stack);
            }
            $parent = $stack ? end($stack)[1] : $top - 1;
            $new = min($max, max($top, $parent + 1));
            $stack[] = [$orig, $new];
            if ($new !== (int) $m[1]) {
                $changed++;
            }

            return '<h'.$new.($m[2] ?? '').'>'.$inner.'</h'.$new.'>';
        }, $html);

        return ['html' => $out ?? $html, 'changed' => $changed, 'removed' => $removed];
    }
}
