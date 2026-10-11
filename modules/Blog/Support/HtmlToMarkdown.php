<?php

namespace Modules\Blog\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Prosty konwerter HTML → Markdown (CommonMark + tabele GFM) do jednorazowego
 * „Przełącz na Markdown" w edytorze Wiem FEER. Bez zależności zewnętrznych.
 *
 * Obsługuje: p, h1–h6, strong/b, em/i, del/s, a, ul/ol/li (zagnieżdżone), blockquote,
 * img, code/pre, hr, br i proste tabele. Wszystko inne (np. <div class="content-note">,
 * <iframe>, <figure>, tabele z colspan) zostaje jako surowy HTML — CommonMark go
 * przepuszcza, więc wygląd wpisu się nie zmienia.
 */
final class HtmlToMarkdown
{
    private const BLOCK_RAW = ['div', 'figure', 'iframe', 'video', 'audio', 'section', 'aside', 'details', 'form', 'dl', 'address', 'picture', 'svg', 'style', 'header', 'footer', 'nav', 'article', 'canvas'];

    private const INLINE_RAW = ['span', 'sup', 'sub', 'abbr', 'mark', 'u', 'small', 'kbd', 'q', 'cite', 'time', 'ins', 'button', 'label', 'input', 'select', 'textarea'];

    public static function convert(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="h2m-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('h2m-root');
        if (! $root) {
            return trim(strip_tags($html));
        }

        $md = (new self)->blocks($root);

        // Najwyżej jedna pusta linia między blokami, bez spacji na końcu (poza twardym łamaniem „\").
        $md = preg_replace("/[ \t]+\n/", "\n", $md);
        $md = preg_replace("/\n{3,}/", "\n\n", $md);

        return trim($md) . "\n";
    }

    /** Ciąg bloków oddzielonych pustą linią; luźny tekst/inline składa się w akapity. */
    private function blocks(DOMNode $parent, string $separator = "\n\n"): string
    {
        $out = [];
        $inline = '';

        foreach ($parent->childNodes as $node) {
            if ($this->isBlock($node)) {
                if (trim($inline) !== '') {
                    $out[] = trim($inline);
                }
                $inline = '';
                $block = $this->block($node);
                if (trim($block) !== '') {
                    $out[] = rtrim($block);
                }
            } else {
                $inline .= $this->inline($node);
            }
        }

        if (trim($inline) !== '') {
            $out[] = trim($inline);
        }

        return implode($separator, $out);
    }

    private function isBlock(DOMNode $node): bool
    {
        if (! $node instanceof DOMElement) {
            return false;
        }

        return in_array($this->tag($node), ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'blockquote', 'pre', 'hr', 'table', ...self::BLOCK_RAW], true);
    }

    private function block(DOMElement $el): string
    {
        $tag = $this->tag($el);

        if ($this->hasMeaningfulAttributes($el) && ! in_array($tag, ['ol', 'pre', 'table'], true)) {
            return $this->raw($el, true);
        }

        switch ($tag) {
            case 'p':
                return trim($this->inlineChildren($el));
            case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6':
                $text = trim(str_replace("\\\n", ' ', $this->inlineChildren($el)));

                return $text === '' ? '' : str_repeat('#', (int) $tag[1]) . ' ' . $text;
            case 'hr':
                return '---';
            case 'blockquote':
                $inner = $this->blocks($el);

                return implode("\n", array_map(fn ($l) => $l === '' ? '>' : '> ' . $l, explode("\n", $inner)));
            case 'ul':
            case 'ol':
                return $this->listBlock($el);
            case 'pre':
                return $this->preBlock($el);
            case 'table':
                return $this->table($el) ?? $this->raw($el, true);
            default:
                return $this->raw($el, true);
        }
    }

    private function listBlock(DOMElement $list): string
    {
        $ordered = $this->tag($list) === 'ol';
        $n = max(1, (int) ($list->getAttribute('start') ?: 1));
        $lines = [];

        foreach ($list->childNodes as $li) {
            if (! $li instanceof DOMElement || $this->tag($li) !== 'li') {
                continue;
            }
            $marker = $ordered ? ($n++) . '. ' : '- ';
            // Bez <p> w środku lista zostaje „ciasna" (zagnieżdżona lista w następnym wierszu).
            $content = $this->blocks($li, $li->getElementsByTagName('p')->length ? "\n\n" : "\n");
            $indent = str_repeat(' ', strlen($marker));
            $itemLines = explode("\n", $content);
            $first = array_shift($itemLines);
            $lines[] = $marker . $first;
            foreach ($itemLines as $l) {
                $lines[] = $l === '' ? '' : $indent . $l;
            }
        }

        // Akapity w <li> dają puste linie (lista „luźna") — zostawiamy je tylko wewnątrz elementu.
        return implode("\n", $lines);
    }

    private function preBlock(DOMElement $pre): string
    {
        $code = $pre;
        $lang = '';
        foreach ($pre->childNodes as $c) {
            if ($c instanceof DOMElement && $this->tag($c) === 'code') {
                $code = $c;
                if (preg_match('/(?:^|\s)language-([\w+-]+)/', $c->getAttribute('class'), $m)) {
                    $lang = $m[1];
                }
                break;
            }
        }
        $text = rtrim($code->textContent, "\n");
        $fence = str_contains($text, '```') ? '~~~~' : '```';

        return $fence . $lang . "\n" . $text . "\n" . $fence;
    }

    /** Prosta tabela → GFM; null, gdy ma scalone komórki, bloki w komórkach albo caption. */
    private function table(DOMElement $table): ?string
    {
        $rows = [];
        $headerRows = 0;
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            $allTh = true;
            foreach ($tr->childNodes as $cell) {
                if (! $cell instanceof DOMElement || ! in_array($this->tag($cell), ['td', 'th'], true)) {
                    continue;
                }
                if ($cell->getAttribute('colspan') > 1 || $cell->getAttribute('rowspan') > 1) {
                    return null;
                }
                foreach ($cell->childNodes as $c) {
                    if ($c instanceof DOMElement && $this->isBlock($c) && $this->tag($c) !== 'p') {
                        return null;
                    }
                }
                if ($this->tag($cell) !== 'th') {
                    $allTh = false;
                }
                $text = trim(preg_replace('/\s*\\\\\n\s*/', '<br>', $this->inlineChildren($cell)));
                $cells[] = str_replace('|', '\|', preg_replace('/\s+/', ' ', $text));
            }
            if ($cells === []) {
                continue;
            }
            $inThead = $tr->parentNode instanceof DOMElement && $this->tag($tr->parentNode) === 'thead';
            if ($rows === [] && ($inThead || $allTh)) {
                $headerRows = 1;
            }
            $rows[] = $cells;
        }

        if ($rows === [] || $table->getElementsByTagName('caption')->length > 0) {
            return null;
        }

        $cols = max(array_map('count', $rows));
        $header = $headerRows ? array_shift($rows) : array_fill(0, $cols, ' ');
        $pad = fn (array $r) => array_pad($r, $cols, '');
        $line = fn (array $r) => '| ' . implode(' | ', $pad($r)) . ' |';

        $out = [$line($header), '| ' . implode(' | ', array_fill(0, $cols, '---')) . ' |'];
        foreach ($rows as $r) {
            $out[] = $line($r);
        }

        return implode("\n", $out);
    }

    private function inlineChildren(DOMNode $el): string
    {
        $s = '';
        foreach ($el->childNodes as $c) {
            $s .= $this->inline($c);
        }

        return $s;
    }

    private function inline(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            $text = preg_replace('/\s+/u', ' ', $node->textContent);

            return $this->escape(str_replace("\u{00A0}", ' ', $text));
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = $this->tag($node);

        if (in_array($tag, self::INLINE_RAW, true) || ($this->hasMeaningfulAttributes($node) && ! in_array($tag, ['a', 'img', 'code'], true))) {
            return $this->raw($node, false);
        }

        switch ($tag) {
            case 'strong':
            case 'b':
                return $this->wrap($this->inlineChildren($node), '**');
            case 'em':
            case 'i':
                return $this->wrap($this->inlineChildren($node), '_');
            case 'del':
            case 's':
            case 'strike':
                return $this->wrap($this->inlineChildren($node), '~~');
            case 'code':
                $t = $node->textContent;
                $fence = str_contains($t, '`') ? '`` ' : '`';

                return $fence . $t . strrev($fence);
            case 'br':
                return "\\\n";
            case 'img':
                $src = $node->getAttribute('src');
                if ($src === '') {
                    return '';
                }
                $title = $node->getAttribute('title');

                return '![' . $this->escape($node->getAttribute('alt')) . '](' . $this->url($src) . ($title !== '' ? ' "' . str_replace('"', '\"', $title) . '"' : '') . ')';
            case 'a':
                $href = $node->getAttribute('href');
                $extra = array_diff(array_map(fn ($a) => strtolower($a->nodeName), iterator_to_array($node->attributes)), ['href', 'title', 'rel']);
                if ($href === '' || $extra !== []) {
                    return $this->raw($node, false);
                }
                $text = $this->inlineChildren($node);
                $title = $node->getAttribute('title');

                return '[' . trim($text) . '](' . $this->url($href) . ($title !== '' ? ' "' . str_replace('"', '\"', $title) . '"' : '') . ')';
            default:
                // Nieznany element inline (albo blokowy wewnątrz akapitu) — surowy HTML.
                return $this->isBlock($node) ? $this->raw($node, false) : $this->inlineChildren($node);
        }
    }

    /** Owija tekst znacznikiem, przenosząc skrajne spacje na zewnątrz (`** x**` nie działa). */
    private function wrap(string $text, string $mark): string
    {
        if (trim($text) === '') {
            return $text;
        }
        preg_match('/^(\s*)(.*?)(\s*)$/su', $text, $m);

        return $m[1] . $mark . $m[2] . $mark . $m[3];
    }

    private function raw(DOMElement $el, bool $block): string
    {
        $html = $el->ownerDocument->saveHTML($el);
        if ($block) {
            // Blok HTML w CommonMark kończy się na pustej linii — usuwamy je ze środka.
            return preg_replace("/\n\s*\n/", "\n", trim($html));
        }

        return preg_replace('/\s*\n\s*/', ' ', $html);
    }

    private function url(string $url): string
    {
        return preg_match('/[\s()<>]/', $url) ? '<' . str_replace(['<', '>'], ['%3C', '%3E'], $url) . '>' : $url;
    }

    private function escape(string $text): string
    {
        $text = str_replace(['\\', '*', '_', '`', '[', ']'], ['\\\\', '\*', '\_', '\`', '\[', '\]'], $text);
        $text = str_replace(['&', '<'], ['&amp;', '&lt;'], $text);

        // Znaki, które na początku wiersza zaczęłyby nagłówek, cytat, listę.
        $text = preg_replace('/^(\s*)([#>+\-])(?=\s)/', '$1\\\\$2', $text);

        return preg_replace('/^(\s*\d+)\.(?=\s)/', '$1\\\\.', $text);
    }

    private function hasMeaningfulAttributes(DOMElement $el): bool
    {
        foreach ($el->attributes as $a) {
            $n = strtolower($a->nodeName);
            if (! in_array($n, ['id', 'dir', 'lang', 'start', 'scope'], true) && ! ($n === 'style' && trim($a->nodeValue) === '')) {
                return true;
            }
        }

        return false;
    }

    private function tag(DOMNode $el): string
    {
        return $el instanceof DOMElement ? strtolower($el->tagName) : '';
    }
}
