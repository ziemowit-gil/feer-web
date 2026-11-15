<?php

namespace App\Services\Content;

use App\Support\BlockRenderer;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;

/**
 * Poprawiator dostępności treści: automatycznie poprawia to, co
 * ContentA11yChecker zgłasza, a da się poprawić bez decyzji redaktora.
 *
 *  - puste nagłówki — usuwa,
 *  - poziomy nagłówków — H1 w treści, start od H3/H4, przeskoki → spójne H2, H3, H4,
 *  - pogrubiony akapit udający śródtytuł → prawdziwy nagłówek,
 *  - akapity zaczynające się od myślnika/numeru → lista punktowana/numerowana,
 *  - tabela bez nagłówków → pierwszy wiersz jako nagłówek kolumn,
 *  - osadzenie bez tytułu → tytuł z nazwy serwisu (np. „Film YouTube”).
 *
 * Czego nie poprawia (wymaga człowieka): teksty linków, opisy obrazów.
 *
 * Treść z edytora blokowego poprawia w blokach (bloki nie są zerowane),
 * zwykłą treść — w HTML. Każdy zapis tworzy wersję w historii zmian, więc
 * poprawkę można cofnąć. Metody: fixHtml(), fixBlocks(), fixRecord().
 */
class ContentA11yFixer
{
    /** Rodzaje poprawek (klucz → opis dla redakcji). */
    public const FIXES = [
        'heading_empty' => 'usunięte puste nagłówki',
        'headings' => 'poprawione poziomy nagłówków',
        'fake_heading' => 'pogrubienia zamienione na śródtytuły',
        'fake_list' => 'myślniki zamienione na listy',
        'table_header' => 'tabele z wierszem nagłówka',
        'iframe_title' => 'tytuły osadzeń',
    ];

    /** Reguły sprawdzarki obsługiwane przez daną poprawkę. */
    public const RULE_MAP = [
        'heading_empty' => 'heading_empty',
        'heading_h1' => 'headings',
        'heading_first' => 'headings',
        'heading_skip' => 'headings',
        'fake_heading' => 'fake_heading',
        'fake_list' => 'fake_list',
        'table_header' => 'table_header',
        'iframe_title' => 'iframe_title',
    ];

    private const MAX_HEADING = 4;

    private const LIST_MARKER = '/^\s*(?:([-–—•*·])|(\d+)[.)])\s+(?=\S)/u';

    /** Rodzaje poprawek do wykonania (puste = wszystkie). */
    private array $only = [];

    /** @param  array<int, string>  $only  klucze z FIXES albo reguły sprawdzarki */
    public function only(array $only): static
    {
        $clone = clone $this;
        $clone->only = array_values(array_unique(array_filter(array_map(
            fn ($k) => self::RULE_MAP[$k] ?? (array_key_exists($k, self::FIXES) ? $k : null),
            $only
        ))));

        return $clone;
    }

    /**
     * Poprawia rekord (strona, aktualność, działanie). Bez $apply tylko liczy, co by poprawił.
     *
     * @return array<string, int> liczba poprawek wg rodzaju
     */
    public function fixRecord(Model $record, bool $apply = false): array
    {
        $blocks = $record->getAttribute('blocks');

        if (is_array($blocks) && $blocks !== []) {
            ['blocks' => $fixed, 'fixes' => $fixes] = $this->fixBlocks($blocks);
            if ($apply && $fixes) {
                $fixed = BlockRenderer::normalize($fixed);
                $record->updateFromBlockEditor(['blocks' => $fixed, 'content' => BlockRenderer::render($fixed)]);
            }

            return $fixes;
        }

        ['html' => $html, 'fixes' => $fixes] = $this->fixHtml((string) $record->getAttribute('content'));
        if ($apply && $fixes) {
            $record->update(['content' => $html]);
        }

        return $fixes;
    }

    /**
     * @return array{html: string, fixes: array<string, int>}
     */
    public function fixHtml(?string $html, int $titleLevel = 1, bool $headingLevels = true): array
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return ['html' => $html, 'fixes' => []];
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="a11y-fix-root">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="a11y-fix-root"]')->item(0);
        if (! $root instanceof DOMElement) {
            return ['html' => $html, 'fixes' => []];
        }

        $fixes = [];
        $count = function (string $key, int $n = 1) use (&$fixes) {
            if ($n > 0) {
                $fixes[$key] = ($fixes[$key] ?? 0) + $n;
            }
        };

        if ($this->enabled('heading_empty')) {
            foreach (iterator_to_array($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root)) as $h) {
                if ($this->text($h) === '' && $xpath->query('.//img', $h)->length === 0) {
                    $h->parentNode->removeChild($h);
                    $count('heading_empty');
                }
            }
        }

        if ($this->enabled('fake_heading')) {
            $count('fake_heading', $this->htmlFakeHeadings($dom, $xpath, $root, $titleLevel));
        }

        if ($this->enabled('fake_list')) {
            $count('fake_list', $this->htmlFakeLists($dom, $root));
        }

        if ($headingLevels && $this->enabled('headings')) {
            $count('headings', $this->htmlHeadingLevels($dom, $xpath, $root, $titleLevel));
        }

        if ($this->enabled('table_header')) {
            foreach (iterator_to_array($xpath->query('.//table', $root)) as $table) {
                if ($xpath->query('.//th', $table)->length === 0 && $this->htmlTableHeader($dom, $xpath, $table)) {
                    $count('table_header');
                }
            }
        }

        if ($this->enabled('iframe_title')) {
            foreach ($xpath->query('.//iframe', $root) as $frame) {
                if (trim($frame->getAttribute('title')) === '' && trim($frame->getAttribute('aria-label')) === '') {
                    $frame->setAttribute('title', self::embedTitle($frame->getAttribute('src')));
                    $count('iframe_title');
                }
            }
        }

        if (! $fixes) {
            return ['html' => $html, 'fixes' => []];
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return ['html' => $out, 'fixes' => $fixes];
    }

    /**
     * Poprawki w drzewie bloków edytora blokowego.
     *
     * @param  array<int, mixed>  $blocks
     * @return array{blocks: array<int, mixed>, fixes: array<string, int>}
     */
    public function fixBlocks(array $blocks): array
    {
        $fixes = [];

        // Najpierw zmiany struktury (usuwanie, zamiana typów), potem poziomy nagłówków w kolejności dokumentu.
        $blocks = $this->blockList($blocks, $fixes);
        if ($this->enabled('headings')) {
            $state = ['last' => 1, 'first' => true, 'offset' => 0];
            $blocks = $this->blockHeadingLevels($blocks, $state, $fixes);
        }

        return ['blocks' => $blocks, 'fixes' => $fixes];
    }

    /** Opis osadzenia na podstawie adresu, gdy redakcja nie podała tytułu. */
    public static function embedTitle(string $src): string
    {
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;

        return match (true) {
            str_contains($host, 'youtube') || $host === 'youtu.be' => 'Film YouTube',
            str_contains($host, 'vimeo') => 'Film Vimeo',
            str_contains($host, 'google') && str_contains($src, 'map') => 'Mapa Google',
            str_contains($host, 'openstreetmap') => 'Mapa OpenStreetMap',
            str_contains($host, 'facebook') => 'Treść z Facebooka',
            str_contains($host, 'spotify') => 'Odtwarzacz Spotify',
            str_contains($host, 'soundcloud') => 'Odtwarzacz SoundCloud',
            str_contains($host, 'docs.google') => 'Dokument Google',
            str_contains($host, 'forms.') || str_contains($src, '/forms/') => 'Formularz',
            $host !== '' => 'Osadzona treść z '.$host,
            default => 'Osadzona treść',
        };
    }

    private function enabled(string $fix): bool
    {
        return $this->only === [] || in_array($fix, $this->only, true);
    }

    // ── HTML ────────────────────────────────────────────────────────────────

    /** Pogrubiony akapit (cały tekst w strong/b, krótki, bez kropki na końcu, nie ostatni) → nagłówek. */
    private function htmlFakeHeadings(DOMDocument $dom, DOMXPath $xpath, DOMElement $root, int $titleLevel): int
    {
        $n = 0;
        $lastReal = null;      // poziom ostatniego prawdziwego nagłówka
        $lastConverted = null; // poziom ostatnio zamienionego pogrubienia

        foreach (iterator_to_array($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6|.//p', $root)) as $el) {
            if ($el->nodeName !== 'p') {
                $lastReal = max($titleLevel + 1, (int) substr($el->nodeName, 1));
                $lastConverted = null;

                continue;
            }
            if (! $this->isFakeHeadingParagraph($xpath, $el)) {
                continue;
            }

            $level = $lastConverted ?? min(self::MAX_HEADING, $lastReal === null ? $titleLevel + 1 : $lastReal + 1);
            $h = $dom->createElement('h'.$level);
            $h->appendChild($dom->createTextNode($this->text($el)));
            $el->parentNode->replaceChild($h, $el);
            $lastConverted = $level;
            $n++;
        }

        return $n;
    }

    private function isFakeHeadingParagraph(DOMXPath $xpath, DOMElement $p): bool
    {
        // Tylko akapity na poziomie treści — nie w tabeli, liście, cytacie, linku.
        for ($node = $p->parentNode; $node instanceof DOMElement && $node->getAttribute('id') !== 'a11y-fix-root'; $node = $node->parentNode) {
            if (in_array($node->nodeName, ['td', 'th', 'li', 'blockquote', 'a', 'figcaption', 'summary', 'button', 'label'], true)) {
                return false;
            }
        }

        $text = $this->text($p);
        if ($text === '' || mb_strlen($text) > 80 || preg_match('/[.:;!?,]$/u', $text) || preg_match(self::LIST_MARKER, $text) || ! $this->nextElement($p)) {
            return false;
        }
        $bold = '';
        foreach ($xpath->query('./strong|./b', $p) as $b) {
            $bold .= $this->text($b);
        }

        return $bold !== '' && $this->normalize($bold) === $this->normalize($text);
    }

    /** Co najmniej dwa sąsiednie akapity z myślnikiem/numerem → ul/ol. */
    private function htmlFakeLists(DOMDocument $dom, DOMElement $root): int
    {
        $n = 0;
        $parents = [$root];
        foreach ((new DOMXPath($dom))->query('.//*[p]', $root) as $el) {
            if ($el !== $root) {
                $parents[] = $el;
            }
        }

        foreach ($parents as $parent) {
            $run = [];
            $flush = function () use (&$run, &$n, $dom) {
                if (count($run) >= 2) {
                    $ordered = collect($run)->every(fn ($p) => preg_match(self::LIST_MARKER, $this->text($p), $m) && ($m[2] ?? '') !== '');
                    $list = $dom->createElement($ordered ? 'ol' : 'ul');
                    $run[0]->parentNode->insertBefore($list, $run[0]);
                    foreach ($run as $p) {
                        $li = $dom->createElement('li');
                        $this->stripListMarker($p);
                        while ($p->firstChild) {
                            $li->appendChild($p->firstChild);
                        }
                        $list->appendChild($li);
                        $p->parentNode->removeChild($p);
                    }
                    $n++;
                }
                $run = [];
            };

            foreach (iterator_to_array($parent->childNodes) as $child) {
                if ($child instanceof DOMText && trim($child->textContent) === '') {
                    continue;
                }
                if ($child instanceof DOMElement && $child->nodeName === 'p' && preg_match(self::LIST_MARKER, $this->text($child))) {
                    $run[] = $child;

                    continue;
                }
                $flush();
            }
            $flush();
        }

        return $n;
    }

    private function stripListMarker(DOMElement $p): void
    {
        $walker = function (DOMNode $node) use (&$walker): bool {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMText) {
                    if (trim($child->textContent) === '') {
                        continue;
                    }
                    $child->nodeValue = preg_replace('/^\s*(?:[-–—•*·]|\d+[.)])\s+/u', '', $child->textContent, 1);

                    return true;
                }
                if ($walker($child)) {
                    return true;
                }
            }

            return false;
        };
        $walker($p);
    }

    /** Pierwszy nagłówek = H2, żadnych przeskoków, H1 w treści → H2. */
    private function htmlHeadingLevels(DOMDocument $dom, DOMXPath $xpath, DOMElement $root, int $titleLevel): int
    {
        $headings = iterator_to_array($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root));
        if (! $headings) {
            return 0;
        }

        $min = $titleLevel + 1;
        $offset = max(0, (int) substr($headings[0]->nodeName, 1) - $min);
        $last = $titleLevel;
        $n = 0;

        foreach ($headings as $h) {
            $level = (int) substr($h->nodeName, 1);
            $new = min(max($min, $level - $offset), $last + 1);
            $last = $new;
            if ($new === $level) {
                continue;
            }
            $this->rename($dom, $h, 'h'.$new);
            $n++;
        }

        return $n;
    }

    /** Pierwszy wiersz tabeli (co najmniej 2 wiersze) → komórki nagłówka kolumn w thead. */
    private function htmlTableHeader(DOMDocument $dom, DOMXPath $xpath, DOMElement $table): bool
    {
        $rows = $xpath->query('./tr|./thead/tr|./tbody/tr|./tfoot/tr', $table);
        if ($rows->length < 2) {
            return false; // jednowierszowa tabela to raczej układ — zostaje dla redakcji
        }
        $first = $rows->item(0);
        foreach (iterator_to_array($xpath->query('./td', $first)) as $td) {
            $th = $this->rename($dom, $td, 'th');
            $th->setAttribute('scope', 'col');
        }

        $thead = $xpath->query('./thead', $table)->item(0);
        if (! $thead) {
            $thead = $dom->createElement('thead');
            $anchor = $xpath->query('./caption|./colgroup', $table);
            $before = $anchor->length ? $this->nextElement($anchor->item($anchor->length - 1)) : $this->firstElement($table);
            $table->insertBefore($thead, $before);
        }
        if ($first->parentNode !== $thead) {
            $first->parentNode->removeChild($first);
            $thead->appendChild($first);
        }

        return true;
    }

    private function rename(DOMDocument $dom, DOMElement $el, string $tag): DOMElement
    {
        $new = $dom->createElement($tag);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->nodeName, $attr->nodeValue);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private function nextElement(DOMNode $node): ?DOMElement
    {
        for ($n = $node->nextSibling; $n; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                return $n;
            }
            if ($n instanceof DOMText && trim($n->textContent) !== '') {
                return null;
            }
        }

        return null;
    }

    private function firstElement(DOMElement $el): ?DOMElement
    {
        foreach ($el->childNodes as $n) {
            if ($n instanceof DOMElement) {
                return $n;
            }
        }

        return null;
    }

    // ── Bloki ───────────────────────────────────────────────────────────────

    /** @param  array<int, mixed>  $blocks */
    private function blockList(array $blocks, array &$fixes): array
    {
        $count = function (string $key, int $n = 1) use (&$fixes) {
            if ($n > 0) {
                $fixes[$key] = ($fixes[$key] ?? 0) + $n;
            }
        };

        $out = [];
        $blocks = array_values(array_filter($blocks, 'is_array'));
        $lastReal = null;
        $lastConverted = null;

        foreach ($blocks as $i => $block) {
            $type = $block['type'] ?? '';
            $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];

            if (is_array($block['inner'] ?? null)) {
                $block['inner'] = $this->blockList($block['inner'], $fixes);
            }

            if ($type === 'heading') {
                if ($this->enabled('heading_empty') && $this->plain($attrs['text'] ?? '') === '') {
                    $count('heading_empty');

                    continue;
                }
                $lastReal = max(2, (int) ($attrs['level'] ?? 2));
                $lastConverted = null;
            }

            if ($type === 'paragraph' && $this->enabled('fake_heading') && isset($blocks[$i + 1]) && $this->isFakeHeadingText((string) ($attrs['text'] ?? ''))) {
                $level = $lastConverted ?? min(self::MAX_HEADING, $lastReal === null ? 2 : $lastReal + 1);
                $block = ['type' => 'heading', 'attrs' => ['text' => e($this->plain($attrs['text'])), 'level' => $level, 'align' => $attrs['align'] ?? 'left']];
                $lastConverted = $level;
                $count('fake_heading');
            }

            if ($type === 'table' && $this->enabled('table_header') && empty($attrs['headerRow']) && empty($attrs['headerColumn']) && count($attrs['rows'] ?? []) >= 2) {
                $block['attrs']['headerRow'] = true;
                $count('table_header');
            }

            if ($type === 'embed' && $this->enabled('iframe_title') && trim((string) ($attrs['title'] ?? '')) === '' && filled($attrs['url'] ?? null)) {
                $block['attrs']['title'] = self::embedTitle((string) $attrs['url']);
                $count('iframe_title');
            }

            if ($type === 'html' && filled($attrs['html'] ?? null)) {
                ['html' => $html, 'fixes' => $htmlFixes] = $this->fixHtml((string) $attrs['html'], 1, false);
                if ($htmlFixes) {
                    $block['attrs']['html'] = $html;
                    foreach ($htmlFixes as $k => $v) {
                        $count($k, $v);
                    }
                }
            }

            $out[] = $block;
        }

        return $this->enabled('fake_list') ? $this->blockFakeLists($out, $count) : $out;
    }

    /** Sąsiednie bloki akapitu z myślnikiem/numerem → blok listy. */
    private function blockFakeLists(array $blocks, callable $count): array
    {
        $out = [];
        $run = [];
        $flush = function () use (&$run, &$out, $count) {
            if (count($run) >= 2) {
                $ordered = collect($run)->every(fn ($b) => preg_match(self::LIST_MARKER, $this->plain($b['attrs']['text'] ?? ''), $m) && ($m[2] ?? '') !== '');
                $out[] = ['type' => 'list', 'attrs' => [
                    'ordered' => $ordered,
                    'items' => array_map(fn ($b) => $this->stripRichMarker((string) ($b['attrs']['text'] ?? '')), $run),
                ]];
                $count('fake_list');
            } else {
                array_push($out, ...$run);
            }
            $run = [];
        };

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'paragraph' && preg_match(self::LIST_MARKER, $this->plain($block['attrs']['text'] ?? ''))) {
                $run[] = $block;

                continue;
            }
            $flush();
            $out[] = $block;
        }
        $flush();

        return $out;
    }

    /** Usuwa znacznik listy z początku tekstu „rich” (może być w środku <strong>). */
    private function stripRichMarker(string $html): string
    {
        return preg_replace('/^((?:\s|<[^>]+>)*)(?:[-–—•*·]|\d+[.)])\s+/u', '$1', $html, 1) ?? $html;
    }

    private function blockHeadingLevels(array $blocks, array &$state, array &$fixes): array
    {
        foreach ($blocks as $i => $block) {
            $type = $block['type'] ?? '';
            $hasTitle = $type === 'heading' || ($type === 'banner' && $this->plain($block['attrs']['title'] ?? '') !== '');

            if ($hasTitle) {
                $level = max(2, (int) ($block['attrs']['level'] ?? 2));
                if ($state['first']) {
                    $state['offset'] = max(0, $level - 2);
                    $state['first'] = false;
                }
                $new = min(self::MAX_HEADING, max(2, min($level - $state['offset'], $state['last'] + 1)));
                $state['last'] = $new;
                if ($new !== $level) {
                    $blocks[$i]['attrs']['level'] = $new;
                    $fixes['headings'] = ($fixes['headings'] ?? 0) + 1;
                }
            }

            if (is_array($block['inner'] ?? null)) {
                $blocks[$i]['inner'] = $this->blockHeadingLevels($block['inner'], $state, $fixes);
            }
        }

        return $blocks;
    }

    private function isFakeHeadingText(string $html): bool
    {
        $text = $this->plain($html);
        if ($text === '' || mb_strlen($text) > 80 || preg_match('/[.:;!?,]$/u', $text) || preg_match(self::LIST_MARKER, $text)) {
            return false;
        }
        preg_match_all('#<(strong|b)\b[^>]*>(.*?)</\1>#isu', $html, $m);
        $bold = $this->plain(implode(' ', $m[2] ?? []));

        return $bold !== '' && $this->normalize($bold) === $this->normalize($text);
    }

    // ── Wspólne ─────────────────────────────────────────────────────────────

    private function plain(mixed $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function text(DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
    }

    private function normalize(string $text): string
    {
        return trim(mb_strtolower(preg_replace('/[\s\p{P}»«›‹→]+/u', ' ', $text) ?? ''));
    }
}
