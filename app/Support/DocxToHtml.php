<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ZipArchive;

/**
 * Parser stylów Worda: .docx → semantyczny HTML, który edytor blokowy rozbija na bloki
 * (htmlToBlocks: nagłówki, akapity, listy, cytaty, tabele, obrazy, ramki), a klasyczny wkleja wprost.
 *
 * Rozpoznaje style po nazwie i dziedziczeniu (basedOn), także polskie („Nagłówek 1”, „Tytuł”, „Cytat”,
 * „Legenda”, „Lista punktowana”) i poziom konspektu (outlineLvl). Listy z numeracji Worda (numbering.xml:
 * punktory → <ul>, reszta → <ol>). Pogrubienie, kursywa, indeksy i linki zostają jako <strong>, <em>,
 * <sup>, <sub>, <a>; kolory, czcionki i rozmiary są pomijane. Krótki, cały pogrubiony akapit bez kropki
 * staje się nagłówkiem (autonagłówek). Tabela z wierszem nagłówka (lub pogrubionym pierwszym wierszem)
 * dostaje <th>; tabela 1×1 (ramka w Wordzie) → <div class="content-box">. Obrazy trafiają do biblioteki
 * mediów przez $storeImage, z tekstem alternatywnym z Worda (docPr descr); akapit w stylu „Legenda” po
 * obrazie to podpis. Poziomy nagłówków porządkuje potem HeadingStructure.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DocxToHtml
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const MAX_IMAGES = 40;

    private const WEB_IMAGES = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'avif'];

    /** @var array{headings:int, auto_headings:int, lists:int, tables:int, quotes:int, images:int, images_skipped:int} */
    public array $stats = ['headings' => 0, 'auto_headings' => 0, 'lists' => 0, 'tables' => 0, 'quotes' => 0, 'images' => 0, 'images_skipped' => 0];

    private ZipArchive $zip;

    private DOMXPath $x;

    /** @var array<string, array{name:string, based:?string, outline:?int, num:?array}> */
    private array $styles = [];

    /** @var array<string, array<int, string>> numId → [ilvl → numFmt] */
    private array $numFormats = [];

    /** @var array<string, string> rId → cel (linki i obrazy) */
    private array $rels = [];

    private array $out = [];

    private ?array $list = null;   // ['tag' => ul|ol, 'items' => []]

    private ?int $lastFigure = null;

    private bool $hasRealHeadings = false;

    /** @param \Closure(string $bytes, string $fileName): ?string|null $storeImage zwraca adres zapisanego obrazu */
    public function __construct(private ?\Closure $storeImage = null) {}

    public function convert(string $path): string
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new \RuntimeException('To nie jest plik .docx.');
        }
        try {
            $doc = $this->load('word/document.xml');
            if (! $doc) {
                throw new \RuntimeException('Brak word/document.xml.');
            }
            $this->readStyles();
            $this->readNumbering();
            $this->readRels();

            $this->x = $this->xpath($doc);
            $body = $this->x->query('/w:document/w:body')->item(0);
            if (! $body) {
                return '';
            }
            $this->hasRealHeadings = $this->detectRealHeadings($body);
            $this->blocks($body);
            $this->closeList();

            return implode("\n", $this->out);
        } finally {
            $this->zip->close();
        }
    }

    // ── Wczytanie części pakietu ───────────────────────────────────────────

    private function load(string $name): ?DOMDocument
    {
        $xml = $this->zip->getFromName($name);
        if ($xml === false || $xml === '') {
            return null;
        }
        $d = new DOMDocument();
        // LIBXML_NONET: bez sieci; encje zewnętrzne są w PHP 8 domyślnie wyłączone.
        if (! @$d->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            return null;
        }

        return $d;
    }

    private function xpath(DOMDocument $d): DOMXPath
    {
        $x = new DOMXPath($d);
        $x->registerNamespace('w', self::W);
        $x->registerNamespace('r', self::R);
        $x->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $x->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $x->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
        $x->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        return $x;
    }

    private function readStyles(): void
    {
        $d = $this->load('word/styles.xml');
        if (! $d) {
            return;
        }
        $x = $this->xpath($d);
        foreach ($x->query('//w:style') as $s) {
            $id = $s->getAttributeNS(self::W, 'styleId');
            if ($id === '') {
                continue;
            }
            $outline = $x->query('w:pPr/w:outlineLvl', $s)->item(0);
            $numId = $x->query('w:pPr/w:numPr/w:numId', $s)->item(0);
            $this->styles[$id] = [
                'name'    => mb_strtolower(trim((string) $x->query('w:name', $s)->item(0)?->getAttributeNS(self::W, 'val'))),
                'based'   => $x->query('w:basedOn', $s)->item(0)?->getAttributeNS(self::W, 'val') ?: null,
                'outline' => $outline ? (int) $outline->getAttributeNS(self::W, 'val') : null,
                'num'     => $numId ? $numId->getAttributeNS(self::W, 'val') : null,
            ];
        }
    }

    private function readNumbering(): void
    {
        $d = $this->load('word/numbering.xml');
        if (! $d) {
            return;
        }
        $x = $this->xpath($d);
        $abstract = [];
        foreach ($x->query('//w:abstractNum') as $a) {
            $levels = [];
            foreach ($x->query('w:lvl', $a) as $lvl) {
                $levels[(int) $lvl->getAttributeNS(self::W, 'ilvl')] = (string) $x->query('w:numFmt', $lvl)->item(0)?->getAttributeNS(self::W, 'val');
            }
            $abstract[$a->getAttributeNS(self::W, 'abstractNumId')] = $levels;
        }
        foreach ($x->query('//w:num') as $n) {
            $aid = $x->query('w:abstractNumId', $n)->item(0)?->getAttributeNS(self::W, 'val');
            $this->numFormats[$n->getAttributeNS(self::W, 'numId')] = $abstract[$aid] ?? [];
        }
    }

    private function readRels(): void
    {
        $d = $this->load('word/_rels/document.xml.rels');
        if (! $d) {
            return;
        }
        foreach ($this->xpath($d)->query('//rel:Relationship') as $r) {
            $this->rels[$r->getAttribute('Id')] = $r->getAttribute('Target');
        }
    }

    // ── Style akapitu ──────────────────────────────────────────────────────

    /** @return array{0:string, 1?:int} rodzaj: h (z poziomem), title, quote, caption, ul, ol, p */
    private function styleKind(?string $id, int $depth = 0): array
    {
        if ($id === null || $depth > 8) {
            return ['p'];
        }
        $s = $this->styles[$id] ?? null;
        $name = $s['name'] ?? '';
        if (preg_match('/^(heading|nagłówek|naglowek)\s*([1-6])$/u', $name, $m) || preg_match('/^(heading|nagwek|naglowek)([1-6])$/i', $id, $m)) {
            return ['h', (int) $m[2]];
        }
        if (in_array($name, ['title', 'tytuł', 'tytul'], true) || in_array($id, ['Title', 'Tytu'], true)) {
            return ['title'];
        }
        if (preg_match('/quote|cytat/u', $name)) {
            return ['quote'];
        }
        if (in_array($name, ['caption', 'legenda'], true)) {
            return ['caption'];
        }
        if (preg_match('/^(list bullet|lista punktowana)/u', $name)) {
            return ['ul'];
        }
        if (preg_match('/^(list number|lista numerowana)/u', $name)) {
            return ['ol'];
        }
        if ($s && $s['outline'] !== null && $s['outline'] < 6) {
            return ['h', $s['outline'] + 1];
        }

        return $s ? $this->styleKind($s['based'], $depth + 1) : ['p'];
    }

    private function detectRealHeadings(DOMElement $body): bool
    {
        foreach ($this->x->query('.//w:p/w:pPr/w:pStyle', $body) as $ps) {
            if (in_array($this->styleKind($ps->getAttributeNS(self::W, 'val'))[0], ['h', 'title'], true)) {
                return true;
            }
        }

        return $this->x->query('.//w:p/w:pPr/w:outlineLvl', $body)->length > 0;
    }

    // ── Bloki ──────────────────────────────────────────────────────────────

    private function blocks(DOMElement $parent): void
    {
        foreach ($parent->childNodes as $n) {
            if (! $n instanceof DOMElement || $n->namespaceURI !== self::W) {
                continue;
            }
            match ($n->localName) {
                'p'   => $this->paragraph($n),
                'tbl' => $this->table($n),
                'sdt' => ($c = $this->x->query('w:sdtContent', $n)->item(0)) ? $this->blocks($c) : null,
                default => null,
            };
        }
    }

    private function paragraph(DOMElement $p): void
    {
        $styleId = $this->x->query('w:pPr/w:pStyle', $p)->item(0)?->getAttributeNS(self::W, 'val') ?: null;
        $kind = $this->styleKind($styleId);
        $outline = $this->x->query('w:pPr/w:outlineLvl', $p)->item(0);
        if ($outline && $kind[0] === 'p' && (int) $outline->getAttributeNS(self::W, 'val') < 6) {
            $kind = ['h', (int) $outline->getAttributeNS(self::W, 'val') + 1];
        }

        $images = [];
        $segments = $this->inline($p, $images, false, false, null);
        $html = $this->render($segments);
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0");

        // Lista: numeracja w akapicie albo w stylu (bez nagłówków numerowanych).
        $numId = $this->x->query('w:pPr/w:numPr/w:numId', $p)->item(0)?->getAttributeNS(self::W, 'val');
        $ilvl = (int) ($this->x->query('w:pPr/w:numPr/w:ilvl', $p)->item(0)?->getAttributeNS(self::W, 'val') ?? 0);
        if ($numId === null && $styleId && ! in_array($kind[0], ['h', 'title'], true)) {
            $numId = $this->styles[$styleId]['num'] ?? null;
        }
        $listTag = null;
        if (! in_array($kind[0], ['h', 'title'], true)) {
            if ($numId !== null && $numId !== '0') {
                $fmt = $this->numFormats[$numId][$ilvl] ?? 'bullet';
                $listTag = in_array($fmt, ['bullet', 'none', ''], true) ? 'ul' : 'ol';
            } elseif (in_array($kind[0], ['ul', 'ol'], true)) {
                $listTag = $kind[0];
            }
        }

        if ($listTag && $text !== '') {
            if (! $this->list || $this->list['tag'] !== $listTag) {
                $this->closeList();
                $this->list = ['tag' => $listTag, 'items' => []];
            }
            $this->list['items'][] = $html;
            $this->images($images);

            return;
        }
        $this->closeList();

        if ($text === '') {
            $this->images($images);

            return;
        }

        if ($kind[0] === 'caption' && $this->lastFigure !== null && $images === []) {
            $this->out[$this->lastFigure] = str_replace('</figure>', '<figcaption>'.$html.'</figcaption></figure>', $this->out[$this->lastFigure]);
            $this->lastFigure = null;

            return;
        }

        $plain = $this->render($segments, false);
        if ($kind[0] === 'h' || $kind[0] === 'title') {
            $level = $kind[0] === 'title' ? 1 : $kind[1];
            $this->emit('<h'.$level.'>'.$plain.'</h'.$level.'>');
            $this->stats['headings']++;
        } elseif ($kind[0] === 'quote') {
            $this->emit('<blockquote><p>'.$html.'</p></blockquote>');
            $this->stats['quotes']++;
        } elseif ($this->looksLikeHeading($segments, $text)) {
            $level = $this->hasRealHeadings ? 3 : 2;
            $this->emit('<h'.$level.'>'.$plain.'</h'.$level.'>');
            $this->stats['auto_headings']++;
        } else {
            $this->emit('<p>'.$html.'</p>');
        }
        $this->images($images);
    }

    /** Krótki akapit w całości pogrubiony, bez kropki na końcu i bez łamania wiersza → śródtytuł. */
    private function looksLikeHeading(array $segments, string $text): bool
    {
        if (mb_strlen($text) < 2 || mb_strlen($text) > 120 || preg_match('/[.,;:!?…]$/u', $text)) {
            return false;
        }
        foreach ($segments as $s) {
            if ($s['br'] || $s['href'] !== null) {
                return false;
            }
            if (! $s['b'] && trim($s['text'], " \t\xC2\xA0") !== '') {
                return false;
            }
        }

        return true;
    }

    private function table(DOMElement $tbl): void
    {
        $this->closeList();
        $rows = [];
        $header = false;
        foreach ($this->x->query('w:tr', $tbl) as $i => $tr) {
            if ($i === 0 && $this->x->query('w:trPr/w:tblHeader', $tr)->length) {
                $header = true;
            }
            $cells = [];
            foreach ($this->x->query('w:tc', $tr) as $tc) {
                $vmerge = $this->x->query('w:tcPr/w:vMerge', $tc)->item(0);
                $continued = $vmerge && $vmerge->getAttributeNS(self::W, 'val') !== 'restart';
                $parts = [];
                $allBold = true;
                foreach ($this->x->query('.//w:p', $tc) as $p) {
                    $images = [];
                    $seg = $this->inline($p, $images, false, false, null);
                    $h = $this->render($seg);
                    if (trim(strip_tags($h)) !== '') {
                        $parts[] = $h;
                        foreach ($seg as $s) {
                            if (! $s['b'] && trim($s['text']) !== '') {
                                $allBold = false;
                            }
                        }
                    }
                }
                $span = (int) ($this->x->query('w:tcPr/w:gridSpan', $tc)->item(0)?->getAttributeNS(self::W, 'val') ?: 1);
                $cells[] = ['html' => $continued ? '' : implode('<br>', $parts), 'bold' => $allBold && $parts, 'span' => max(1, $span)];
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if (! $rows) {
            return;
        }

        // Ramka z Worda (tabela 1×1) → ramka treści.
        if (count($rows) === 1 && count($rows[0]) === 1) {
            $paras = [];
            foreach ($this->x->query('.//w:p', $tbl) as $p) {
                $images = [];
                $h = $this->render($this->inline($p, $images, false, false, null));
                if (trim(strip_tags($h)) !== '') {
                    $paras[] = '<p>'.$h.'</p>';
                }
            }
            if ($paras) {
                $this->emit('<div class="content-box">'.implode('', $paras).'</div>');
            }

            return;
        }

        if (! $header && count($rows) > 1) {
            $header = array_reduce($rows[0], fn ($c, $cell) => $c && ($cell['bold'] || $cell['html'] === ''), true)
                && array_filter($rows[0], fn ($cell) => $cell['html'] !== '');
        }
        $html = '<table>';
        foreach ($rows as $i => $cells) {
            $tag = $header && $i === 0 ? 'th' : 'td';
            if ($header && $i === 0) {
                $html .= '<thead>';
            }
            $html .= '<tr>';
            foreach ($cells as $c) {
                $inner = $tag === 'th' ? strip_tags($c['html'], '<br><a><em>') : $c['html'];
                $html .= '<'.$tag.($tag === 'th' ? ' scope="col"' : '').($c['span'] > 1 ? ' colspan="'.$c['span'].'"' : '').'>'.$inner.'</'.$tag.'>';
            }
            $html .= '</tr>';
            if ($header && $i === 0) {
                $html .= '</thead><tbody>';
            }
        }
        $html .= ($header ? '</tbody>' : '').'</table>';
        $this->emit($html);
        $this->stats['tables']++;
    }

    // ── Tekst w akapicie ───────────────────────────────────────────────────

    /** @return list<array{text:string, b:bool, i:bool, sup:bool, sub:bool, href:?string, br:bool}> */
    private function inline(DOMElement $node, array &$images, bool $b, bool $i, ?string $href): array
    {
        $out = [];
        foreach ($node->childNodes as $c) {
            if (! $c instanceof DOMElement) {
                continue;
            }
            if ($c->namespaceURI !== self::W) {
                continue;
            }
            switch ($c->localName) {
                case 'r':
                    array_push($out, ...$this->run($c, $images, $b, $i, $href));
                    break;
                case 'hyperlink':
                    $rid = $c->getAttributeNS(self::R, 'id');
                    $url = $rid !== '' ? ($this->rels[$rid] ?? null) : null;
                    if ($url !== null && ! preg_match('#^(https?://|mailto:|tel:)#i', $url)) {
                        $url = null;
                    }
                    array_push($out, ...$this->inline($c, $images, $b, $i, $url ?? $href));
                    break;
                case 'ins': case 'smartTag': case 'fldSimple': case 'customXml':
                    array_push($out, ...$this->inline($c, $images, $b, $i, $href));
                    break;
                case 'sdt':
                    if ($sc = $this->x->query('w:sdtContent', $c)->item(0)) {
                        array_push($out, ...$this->inline($sc, $images, $b, $i, $href));
                    }
                    break;
            }
        }

        return $out;
    }

    private function run(DOMElement $r, array &$images, bool $b, bool $i, ?string $href): array
    {
        $on = fn (string $tag) => ($el = $this->x->query('w:rPr/w:'.$tag, $r)->item(0))
            && ! in_array($el->getAttributeNS(self::W, 'val'), ['0', 'false', 'off'], true);
        $rStyle = mb_strtolower((string) $this->x->query('w:rPr/w:rStyle', $r)->item(0)?->getAttributeNS(self::W, 'val'));
        $bold = $b || $on('b') || in_array($rStyle, ['strong', 'pogrubienie'], true);
        $italic = $i || $on('i') || in_array($rStyle, ['emphasis', 'uwydatnienie'], true);
        $va = (string) $this->x->query('w:rPr/w:vertAlign', $r)->item(0)?->getAttributeNS(self::W, 'val');
        $hidden = $on('vanish');

        $out = [];
        $text = '';
        $flush = function () use (&$text, &$out, $bold, $italic, $va, $href) {
            if ($text !== '') {
                $out[] = ['text' => $text, 'b' => $bold, 'i' => $italic, 'sup' => $va === 'superscript', 'sub' => $va === 'subscript', 'href' => $href, 'br' => false];
                $text = '';
            }
        };
        foreach ($r->childNodes as $c) {
            if (! $c instanceof DOMElement) {
                continue;
            }
            if ($c->namespaceURI === self::W) {
                switch ($c->localName) {
                    case 't': if (! $hidden) { $text .= $c->textContent; } break;
                    case 'tab': $text .= ' '; break;
                    case 'noBreakHyphen': $text .= '-'; break;
                    case 'softHyphen': break;
                    case 'br': case 'cr':
                        if ($c->getAttributeNS(self::W, 'type') === 'page') {
                            break;
                        }
                        $flush();
                        $out[] = ['text' => '', 'b' => false, 'i' => false, 'sup' => false, 'sub' => false, 'href' => null, 'br' => true];
                        break;
                    case 'drawing': case 'pict': case 'object':
                        $flush();
                        $this->collectImages($c, $images);
                        break;
                }
            } elseif ($c->localName === 'AlternateContent') {
                $flush();
                $this->collectImages($c, $images);
            }
        }
        $flush();

        return $out;
    }

    private function render(array $segments, bool $format = true): string
    {
        // Sklejenie sąsiednich fragmentów o tym samym formatowaniu (Word dzieli tekst na wiele przebiegów).
        $merged = [];
        foreach ($segments as $s) {
            $last = $merged ? $merged[count($merged) - 1] : null;
            if ($last && ! $s['br'] && ! $last['br'] && $last['b'] === $s['b'] && $last['i'] === $s['i']
                && $last['sup'] === $s['sup'] && $last['sub'] === $s['sub'] && $last['href'] === $s['href']) {
                $merged[count($merged) - 1]['text'] .= $s['text'];
            } else {
                $merged[] = $s;
            }
        }
        $html = '';
        foreach ($merged as $s) {
            if ($s['br']) {
                $html .= '<br>';

                continue;
            }
            $t = e(preg_replace('/ {2,}/', ' ', $s['text']));
            if ($format && trim($s['text']) !== '') {
                if ($s['sup']) { $t = '<sup>'.$t.'</sup>'; }
                if ($s['sub']) { $t = '<sub>'.$t.'</sub>'; }
                if ($s['i']) { $t = '<em>'.$t.'</em>'; }
                if ($s['b']) { $t = '<strong>'.$t.'</strong>'; }
            }
            if ($s['href'] !== null) {
                $t = '<a href="'.e($s['href']).'">'.$t.'</a>';
            }
            $html .= $t;
        }
        $html = preg_replace('#(<br>)+$#', '', preg_replace('#^(<br>)+#', '', $html));

        return trim($html);
    }

    // ── Obrazy ─────────────────────────────────────────────────────────────

    private function collectImages(DOMElement $node, array &$images): void
    {
        // AlternateContent: Choice i Fallback zawierają ten sam obraz — bierzemy tylko pierwszą gałąź.
        if ($node->localName === 'AlternateContent') {
            foreach ($node->childNodes as $c) {
                if ($c instanceof DOMElement) {
                    $this->collectImages($c, $images);

                    return;
                }
            }

            return;
        }
        foreach ($this->x->query('.//a:blip', $node) as $blip) {
            $rid = $blip->getAttributeNS(self::R, 'embed');
            $doc = $this->x->query('ancestor::wp:inline/wp:docPr | ancestor::wp:anchor/wp:docPr', $blip)->item(0);
            $images[] = ['rid' => $rid, 'alt' => trim((string) ($doc?->getAttribute('descr') ?: $doc?->getAttribute('title')))];
        }
        foreach ($this->x->query('.//v:imagedata', $node) as $im) {
            $images[] = ['rid' => $im->getAttributeNS(self::R, 'id'), 'alt' => trim((string) $im->getAttributeNS('urn:schemas-microsoft-com:office:office', 'title'))];
        }
    }

    private function images(array $images): void
    {
        foreach ($images as $img) {
            $target = $this->rels[$img['rid']] ?? null;
            if ($target === null || ! $this->storeImage || $this->stats['images'] >= self::MAX_IMAGES) {
                $this->stats['images_skipped']++;

                continue;
            }
            $path = 'word/'.ltrim(preg_replace('#^\.?/?#', '', $target), '/');
            if (str_starts_with($target, '/')) {
                $path = ltrim($target, '/');
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $bytes = in_array($ext, self::WEB_IMAGES, true) ? $this->zip->getFromName($path) : false;
            $url = $bytes ? ($this->storeImage)($bytes, basename($path)) : null;
            if (! $url) {
                $this->stats['images_skipped']++;

                continue;
            }
            $this->stats['images']++;
            $this->emit('<figure><img src="'.e($url).'" alt="'.e($img['alt']).'"></figure>');
            $this->lastFigure = count($this->out) - 1;
        }
    }

    // ── Wyjście ────────────────────────────────────────────────────────────

    private function emit(string $html): void
    {
        $this->out[] = $html;
        $this->lastFigure = null;
    }

    private function closeList(): void
    {
        if ($this->list && $this->list['items']) {
            $this->out[] = '<'.$this->list['tag'].'>'.implode('', array_map(fn ($li) => '<li>'.$li.'</li>', $this->list['items'])).'</'.$this->list['tag'].'>';
            $this->stats['lists']++;
            $this->lastFigure = null;
        }
        $this->list = null;
    }
}
