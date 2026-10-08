<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Minimalny sanitizer HTML z białą listą — dla treści z zewnętrznego systemu
 * (klauzule RODO z SZO). SZO i tak escapuje treść po swojej stronie, ale strona
 * publiczna nie powinna ufać cudzemu HTML-owi w ciemno (obrona w głąb).
 *
 * Zostają: akapity, nagłówki h2–h4, listy, pogrubienia/kursywa, <br>, <a href>
 * z http(s)/mailto/tel. Nieznane tagi są „rozpakowywane” (zostaje ich tekst),
 * a <script>/<style>/<iframe> itp. usuwane razem z treścią. Wszystkie atrybuty
 * poza href znikają; linki zewnętrzne dostają rel="noopener".
 */
class SafeHtml
{
    private const ALLOWED = ['p', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'br', 'a', 'blockquote'];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'];

    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="safe-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('safe-root');
        if (! $root) {
            return e(strip_tags($html));
        }

        self::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * Obniża poziom nagłówków w oczyszczonym HTML (np. h2→h4 przy $by = 2), żeby
     * treść osadzona pod nagłówkiem sekcji nie łamała hierarchii strony (WCAG 1.3.1).
     */
    public static function demoteHeadings(string $html, int $by): string
    {
        if ($by <= 0) {
            return $html;
        }

        return preg_replace_callback('#<(/?)h([1-6])>#i', function ($m) use ($by) {
            return '<' . $m[1] . 'h' . min(6, (int) $m[2] + $by) . '>';
        }, $html);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // Rozpakuj: dzieci trafiają na miejsce elementu
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if (preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                    $child->setAttribute('href', $href);
                    if (preg_match('#^https?://#i', $href)) {
                        $child->setAttribute('rel', 'noopener');
                    }
                } else {
                    // Link bez bezpiecznego protokołu (np. javascript:) → sam tekst
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
            }
        }
    }
}
