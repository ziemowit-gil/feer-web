<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Edytor blokowy (styl Gutenberga): normalizacja drzewa bloków przesłanego
 * z przeglądarki i renderowanie go do HTML zapisywanego w kolumnie `content`.
 *
 * Blok: ['type' => 'paragraph', 'attrs' => [...], 'inner' => [bloki]]. Nieznane
 * typy i atrybuty są odrzucane, a tekst z pól „rich" przechodzi przez białą
 * listę znaczników inline. Wyjątek: bloki `html` i `shortcode` — zaufany kod
 * redakcji, tak samo jak treść z dotychczasowego edytora WYSIWYG.
 *
 * Renderowany HTML korzysta z klas, które front już stylizuje (cta-button,
 * content-columns, content-box, content-note, content-important, content-gallery),
 * więc strona wygląda tak samo jak treść z klasycznego edytora.
 */
class BlockRenderer
{
    public const MAX_DEPTH = 4;

    public const MAX_BLOCKS = 500;

    /** Typy z zagnieżdżonymi blokami i dozwolone typy dzieci (null = dowolne nie-kontenerowe). */
    private const CONTAINERS = [
        'columns' => ['column'],
        'column' => null,
        'callout' => null,
        'details' => null,
    ];

    public const TYPES = [
        'paragraph', 'heading', 'list', 'quote', 'image', 'gallery', 'buttons', 'callout',
        'columns', 'column', 'details', 'table', 'separator', 'embed', 'file', 'html', 'shortcode', 'subpages', 'newslist', 'banner', 'imagebanner',
    ];

    private const INLINE_TAGS = ['strong', 'b', 'em', 'i', 'a', 'br', 'code', 's', 'sub', 'sup', 'mark'];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript'];

    private int $count = 0;

    /**
     * Czyści drzewo bloków: tylko znane typy, znane atrybuty, ograniczona głębokość i liczba bloków.
     *
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $blocks): array
    {
        return (new self)->normalizeList($blocks, 0, null);
    }

    /** @param  array<int, mixed>  $blocks */
    public static function render(array $blocks): string
    {
        $renderer = new self;

        return $renderer->renderList($renderer->normalizeList($blocks, 0, null));
    }

    /** @param  array<int, mixed>  $blocks */
    private function normalizeList(array $blocks, int $depth, ?array $allowed): array
    {
        $out = [];
        foreach ($blocks as $block) {
            if (! is_array($block) || $this->count >= self::MAX_BLOCKS) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if (! in_array($type, self::TYPES, true)) {
                continue;
            }
            if ($allowed !== null && ! in_array($type, $allowed, true)) {
                continue;
            }
            // „column" ma sens tylko wewnątrz „columns".
            if ($type === 'column' && $allowed === null) {
                continue;
            }
            // Kontenery nie zagnieżdżają się w nieskończoność.
            if (array_key_exists($type, self::CONTAINERS) && $type !== 'column' && $depth >= self::MAX_DEPTH) {
                continue;
            }

            $this->count++;
            $attrs = $this->normalizeAttrs($type, is_array($block['attrs'] ?? null) ? $block['attrs'] : []);
            $normalized = ['type' => $type, 'attrs' => $attrs];

            if (array_key_exists($type, self::CONTAINERS)) {
                $childAllowed = self::CONTAINERS[$type];
                $inner = is_array($block['inner'] ?? null) ? $block['inner'] : [];
                $normalized['inner'] = $this->normalizeList($inner, $depth + 1, $childAllowed);
                if ($type === 'columns') {
                    $normalized['inner'] = array_slice($normalized['inner'], 0, 4);
                }
            }

            $out[] = $normalized;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function normalizeAttrs(string $type, array $a): array
    {
        $str = fn (string $key, int $max = 2000) => mb_substr(trim((string) ($a[$key] ?? '')), 0, $max);
        $rich = fn (string $key, int $max = 20000) => self::inline(mb_substr((string) ($a[$key] ?? ''), 0, $max));
        $pick = fn (string $key, array $options, string $default) => in_array($a[$key] ?? null, $options, true) ? $a[$key] : $default;
        $align = fn () => $pick('align', ['left', 'center', 'right'], 'left');

        return match ($type) {
            'paragraph' => ['text' => $rich('text'), 'align' => $align(), 'lead' => (bool) ($a['lead'] ?? false)],
            'heading' => ['text' => $rich('text', 500), 'level' => max(2, min(4, (int) ($a['level'] ?? 2))), 'align' => $align(), 'anchor' => self::anchor($str('anchor', 80))],
            'list' => [
                'ordered' => (bool) ($a['ordered'] ?? false),
                'items' => array_values(array_filter(array_map(
                    fn ($item) => self::inline(mb_substr((string) $item, 0, 5000)),
                    array_slice(is_array($a['items'] ?? null) ? $a['items'] : [], 0, 200)
                ), fn ($item) => trim(strip_tags($item)) !== '')),
            ],
            'quote' => ['text' => $rich('text'), 'cite' => $rich('cite', 500)],
            'image' => [
                'url' => self::url($str('url')),
                'alt' => $str('alt', 500),
                'decorative' => (bool) ($a['decorative'] ?? false),
                'caption' => $rich('caption', 1000),
                'width' => $pick('width', ['full', 'wide', 'medium', 'small'], 'full'),
                'href' => self::url($str('href')),
            ],
            'gallery' => [
                'columns' => max(2, min(4, (int) ($a['columns'] ?? 3))),
                'images' => array_values(array_filter(array_map(
                    fn ($img) => is_array($img) ? [
                        'url' => self::url(mb_substr((string) ($img['url'] ?? ''), 0, 2000)),
                        'alt' => mb_substr(trim((string) ($img['alt'] ?? '')), 0, 500),
                        'caption' => mb_substr(trim((string) ($img['caption'] ?? '')), 0, 300),
                    ] : null,
                    array_slice(is_array($a['images'] ?? null) ? $a['images'] : [], 0, 60)
                ), fn ($img) => $img && $img['url'] !== '')),
            ],
            'buttons' => [
                'align' => $align(),
                'items' => array_values(array_filter(array_map(
                    fn ($btn) => is_array($btn) ? [
                        'label' => mb_substr(trim((string) ($btn['label'] ?? '')), 0, 120),
                        'url' => self::url(mb_substr((string) ($btn['url'] ?? ''), 0, 2000)),
                        'variant' => in_array($btn['variant'] ?? null, ['brand', 'red', 'green'], true) ? $btn['variant'] : 'brand',
                        'newTab' => (bool) ($btn['newTab'] ?? false),
                    ] : null,
                    array_slice(is_array($a['items'] ?? null) ? $a['items'] : [], 0, 6)
                ), fn ($btn) => $btn && $btn['label'] !== '')),
            ],
            'callout' => ['variant' => $pick('variant', ['box', 'note', 'important'], 'box')],
            'columns' => ['ratio' => $pick('ratio', ['50-50', '33-67', '67-33', 'equal'], 'equal')],
            'details' => ['summary' => $str('summary', 300), 'open' => (bool) ($a['open'] ?? false)],
            'table' => [
                'caption' => $str('caption', 500),
                'headerRow' => (bool) ($a['headerRow'] ?? true),
                'headerColumn' => (bool) ($a['headerColumn'] ?? false),
                'rows' => self::tableRows($a['rows'] ?? []),
            ],
            'embed' => ['url' => self::url($str('url')), 'title' => $str('title', 300), 'caption' => $rich('caption', 1000)],
            'file' => ['url' => self::url($str('url')), 'label' => $str('label', 300), 'description' => $str('description', 500)],
            'banner' => [
                'title' => preg_replace('/\s+/u', ' ', $str('title', 200)),
                'level' => max(2, min(4, (int) ($a['level'] ?? 2))),
                'url' => self::url($str('url')),
                'alt' => $str('alt', 500),
                'decorative' => (bool) ($a['decorative'] ?? true),
                'size' => $pick('size', ['large', 'small'], 'large'),
                'tone' => $pick('tone', ['gray', 'brand'], 'gray'),
                'href' => self::url($str('href')),
            ],
            'imagebanner' => [
                'url' => self::url($str('url')),
                'alt' => preg_replace('/\s+/u', ' ', $str('alt', 500)),
                'href' => self::url($str('href')),
                'newTab' => (bool) ($a['newTab'] ?? false),
                // Wymiary grafiki w pikselach (odczytane w edytorze) — atrybuty width/height, by strona nie „skakała” przy ładowaniu.
                'width' => max(0, min(10000, (int) ($a['width'] ?? 0))),
                'height' => max(0, min(10000, (int) ($a['height'] ?? 0))),
            ],
            'html' => ['html' => mb_substr((string) ($a['html'] ?? ''), 0, 500000)],
            'subpages' => self::subpagesAttrs($a),
            'newslist' => NewsList::normalize($a),
            'shortcode' => ['code' => preg_match('/^\[[a-z0-9_-]+(?::[^\]\r\n]{1,200})?\]$/i', $str('code', 220)) ? $str('code', 220) : ''],
            default => [],
        };
    }

    /**
     * Blok „Lista podstron": źródło (bieżąca / wskazana strona), opcjonalny wybór ręczny, sortowanie, głębokość, wygląd.
     *
     * @return array<string, mixed>
     */
    private static function subpagesAttrs(array $a): array
    {
        $o = SubpageList::normalize([
            'page' => $a['page'] ?? null,
            'picked' => $a['picked'] ?? [],
            'sort' => $a['sort'] ?? null,
            'depth' => $a['depth'] ?? null,
            'layout' => $a['layout'] ?? null,
        ]);
        $source = ($a['source'] ?? null) === 'page' && $o['page'] ? 'page' : 'current';

        return [
            'source' => $source,
            'page' => $source === 'page' ? $o['page'] : null,
            'manual' => (bool) ($a['manual'] ?? false) && $o['picked'] !== [],
            'picked' => $o['picked'],
            'sort' => $o['sort'],
            'depth' => $o['depth'],
            'layout' => $o['layout'],
        ];
    }

    /** @return array<int, array<int, string>> */
    private static function tableRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }
        $rows = array_slice(array_values(array_filter($rows, 'is_array')), 0, 200);
        $width = min(12, max(1, ...array_map(fn ($r) => count($r), $rows ?: [[0]])));

        return array_map(function ($row) use ($width) {
            $row = array_values(array_slice($row, 0, $width));

            return array_map(fn ($i) => self::inline(mb_substr((string) ($row[$i] ?? ''), 0, 5000)), range(0, $width - 1));
        }, $rows);
    }

    /** @param  array<int, array<string, mixed>>  $blocks */
    private function renderList(array $blocks): string
    {
        return implode("\n", array_filter(array_map(fn ($b) => $this->renderBlock($b), $blocks), fn ($html) => $html !== ''));
    }

    /** @param  array<string, mixed>  $b */
    private function renderBlock(array $b): string
    {
        $a = $b['attrs'];
        $inner = fn () => $this->renderList($b['inner'] ?? []);
        $alignClass = fn () => ($a['align'] ?? 'left') !== 'left' ? ' class="has-text-align-'.$a['align'].'"' : '';

        switch ($b['type']) {
            case 'paragraph':
                if (trim(strip_tags($a['text'], '<br>')) === '' && ! str_contains($a['text'], '<br')) {
                    return '';
                }
                $classes = array_filter([$a['align'] !== 'left' ? 'has-text-align-'.$a['align'] : null, $a['lead'] ? 'is-lead' : null]);

                return '<p'.($classes ? ' class="'.implode(' ', $classes).'"' : '').'>'.$a['text'].'</p>';

            case 'heading':
                if (trim(strip_tags($a['text'])) === '') {
                    return '';
                }
                $tag = 'h'.$a['level'];
                $id = $a['anchor'] !== '' ? ' id="'.e($a['anchor']).'"' : '';

                return "<{$tag}{$id}{$alignClass()}>{$a['text']}</{$tag}>";

            case 'list':
                if ($a['items'] === []) {
                    return '';
                }
                $tag = $a['ordered'] ? 'ol' : 'ul';

                return "<{$tag}>".implode('', array_map(fn ($item) => "<li>{$item}</li>", $a['items']))."</{$tag}>";

            case 'quote':
                if (trim(strip_tags($a['text'])) === '') {
                    return '';
                }
                $cite = trim(strip_tags($a['cite'])) !== '' ? '<figcaption>— '.$a['cite'].'</figcaption>' : '';

                return '<figure class="content-quote"><blockquote><p>'.$a['text'].'</p></blockquote>'.$cite.'</figure>';

            case 'image':
                if ($a['url'] === '') {
                    return '';
                }
                $alt = $a['decorative'] ? '' : $a['alt'];
                $img = '<img src="'.e($a['url']).'" alt="'.e($alt).'" loading="lazy">';
                if ($a['href'] !== '') {
                    $img = '<a href="'.e($a['href']).'">'.$img.'</a>';
                }
                $caption = trim(strip_tags($a['caption'])) !== '' ? '<figcaption>'.$a['caption'].'</figcaption>' : '';

                return '<figure class="content-figure content-figure--'.$a['width'].'">'.$img.$caption.'</figure>';

            case 'gallery':
                if ($a['images'] === []) {
                    return '';
                }
                $cols = $a['columns'] !== 3 ? ' content-gallery--cols-'.$a['columns'] : '';
                $figures = array_map(fn ($img) => '<figure><img src="'.e($img['url']).'" alt="'.e($img['alt']).'" loading="lazy">'
                    .($img['caption'] !== '' ? '<figcaption>'.e($img['caption']).'</figcaption>' : '').'</figure>', $a['images']);

                return '<div class="content-gallery'.$cols.'" role="group" aria-label="Galeria zdjęć">'.implode('', $figures).'</div>';

            case 'buttons':
                if ($a['items'] === []) {
                    return '';
                }
                $links = array_map(function ($btn) {
                    $class = 'cta-button'.($btn['variant'] !== 'brand' ? ' cta-'.$btn['variant'] : '');
                    $target = $btn['newTab'] ? ' target="_blank" rel="noopener"' : '';
                    $sr = $btn['newTab'] ? '<span class="sr-only"> (otwiera się w nowej karcie)</span>' : '';

                    return '<a href="'.e($btn['url'] ?: '#').'" class="'.$class.'"'.$target.'>'.e($btn['label']).$sr.'</a>';
                }, $a['items']);
                $align = $a['align'] !== 'left' ? ' has-text-align-'.$a['align'] : '';

                return '<p class="content-buttons'.$align.'">'.implode(' ', $links).'</p>';

            case 'callout':
                $body = $inner();
                if ($body === '') {
                    return '';
                }
                $class = ['box' => 'content-box', 'note' => 'content-note', 'important' => 'content-important'][$a['variant']];

                return '<div class="'.$class.'">'.$body.'</div>';

            case 'columns':
                $cols = array_map(fn ($col) => '<div class="content-column">'.$this->renderList($col['inner'] ?? []).'</div>', $b['inner'] ?? []);
                if ($cols === []) {
                    return '';
                }
                $ratio = $a['ratio'] !== 'equal' ? ' content-columns--'.$a['ratio'] : '';

                return '<div class="content-columns content-columns--'.count($cols).$ratio.'">'.implode('', $cols).'</div>';

            case 'column':
                return $inner();

            case 'details':
                if ($a['summary'] === '') {
                    return '';
                }

                return '<details class="content-details"'.($a['open'] ? ' open' : '').'><summary>'.e($a['summary']).'</summary>'
                    .'<div class="content-details-body">'.$inner().'</div></details>';

            case 'table':
                return $this->renderTable($a);

            case 'separator':
                return '<hr>';

            case 'embed':
                $src = self::embedSrc($a['url']);
                if ($src === null) {
                    return $a['url'] !== '' ? '<p><a href="'.e($a['url']).'">'.e($a['title'] ?: $a['url']).'</a></p>' : '';
                }
                $caption = trim(strip_tags($a['caption'])) !== '' ? '<figcaption>'.$a['caption'].'</figcaption>' : '';

                return '<figure class="content-embed"><div class="content-embed-frame"><iframe src="'.e($src).'" title="'.e($a['title'] ?: 'Osadzony film').'"'
                    .' loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></div>'
                    .$caption.'</figure>';

            case 'file':
                if ($a['url'] === '') {
                    return '';
                }
                $ext = strtoupper(pathinfo(parse_url($a['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                $label = $a['label'] !== '' ? $a['label'] : basename(parse_url($a['url'], PHP_URL_PATH) ?: $a['url']);
                $desc = $a['description'] !== '' ? '<span class="content-file-desc">'.e($a['description']).'</span>' : '';

                return '<p class="content-file"><a href="'.e($a['url']).'" download>'.e($label)
                    .($ext !== '' ? ' <span class="content-file-meta">('.e($ext).')</span>' : '').'</a>'.$desc.'</p>';

            case 'banner':
                return $this->renderBanner($a);

            case 'imagebanner':
                // Baner graficzny: cała grafika (z tekstem na niej) jako jeden link. Tekst alternatywny = tekst z grafiki,
                // więc jest też nazwą linku; „nowa karta” ogłaszana czytnikom ekranu.
                if ($a['url'] === '') {
                    return '';
                }
                $size = $a['width'] > 0 && $a['height'] > 0 ? ' width="'.$a['width'].'" height="'.$a['height'].'"' : '';
                $img = '<img src="'.e($a['url']).'" alt="'.e($a['alt']).'"'.$size.' loading="lazy">';
                if ($a['href'] !== '') {
                    $target = $a['newTab'] ? ' target="_blank" rel="noopener"' : '';
                    $sr = $a['newTab'] ? '<span class="sr-only"> (otwiera się w nowej karcie)</span>' : '';
                    $img = '<a href="'.e($a['href']).'" class="content-imagebanner-link"'.$target.'>'.$img.$sr.'</a>';
                }

                return '<div class="content-imagebanner">'.$img.'</div>';

            case 'html':
                return trim($a['html']);

            case 'shortcode':
                return $a['code'] !== '' ? '<p>'.$a['code'].'</p>' : '';

            case 'subpages':
                // Shortcode, nie gotowy HTML — lista składa się przy renderowaniu strony (nowe podstrony pojawiają się same).
                return '<p>'.SubpageList::shortcode([
                    'page' => $a['page'],
                    'picked' => $a['manual'] ? $a['picked'] : [],
                    'sort' => $a['sort'],
                    'depth' => $a['depth'],
                    'layout' => $a['layout'],
                ]).'</p>';

            case 'newslist':
                return '<p>'.NewsList::shortcode($a).'</p>';
        }

        return '';
    }

    /** @param  array<string, mixed>  $a */
    private function renderTable(array $a): string
    {
        $rows = $a['rows'];
        if ($rows === []) {
            return '';
        }
        $html = '<table>';
        if ($a['caption'] !== '') {
            $html .= '<caption>'.e($a['caption']).'</caption>';
        }
        if ($a['headerRow']) {
            $head = array_shift($rows);
            $html .= '<thead><tr>'.implode('', array_map(fn ($c) => '<th scope="col">'.$c.'</th>', $head)).'</tr></thead>';
        }
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $i => $cell) {
                $html .= ($i === 0 && $a['headerColumn']) ? '<th scope="row">'.$cell.'</th>' : '<td>'.$cell.'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    /** Adres YouTube / Vimeo → adres osadzenia (YouTube w trybie bez ciasteczek). */
    public static function embedSrc(string $url): ?string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?dnt=1';
        }

        return null;
    }

    /**
     * Baner sekcji: grafika po lewej ścięta ukosem, dwie ukośne linie i tytuł sekcji (nagłówek) po prawej.
     * Grafika domyślnie dekoracyjna (alt=""), bo nazwę niesie tytuł. Z odnośnikiem cały baner jest jednym
     * linkiem (tytuł rozciągnięty na baner). Style: .content-banner w app.css.
     *
     * @param  array<string, mixed>  $a
     */
    private function renderBanner(array $a): string
    {
        if ($a['title'] === '') {
            return '';
        }
        $tag = 'h'.$a['level'];
        $title = e($a['title']);
        if ($a['href'] !== '') {
            $title = '<a href="'.e($a['href']).'" class="content-banner-link">'.$title.'</a>';
        }
        $media = '';
        if ($a['url'] !== '') {
            $alt = $a['decorative'] ? '' : $a['alt'];
            $media = '<div class="content-banner-media"><img src="'.e($a['url']).'" alt="'.e($alt).'" loading="lazy"></div>'
                .'<svg class="content-banner-lines" viewBox="0 0 45 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
                .'<line x1="36" y1="0" x2="6" y2="100" /><line x1="44" y1="0" x2="14" y2="100" /></svg>';
        }
        $classes = 'content-banner content-banner--'.$a['size'].' content-banner--'.$a['tone'].($media === '' ? ' content-banner--no-media' : '');

        return '<div class="'.$classes.'">'.$media.'<'.$tag.' class="content-banner-title">'.$title.'</'.$tag.'></div>';
    }

    /** Tylko adresy względne, kotwice, http(s), mailto i tel — bez javascript:/data:. */
    public static function url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('~^(https?://|mailto:|tel:|/(?!/)|#|\?)~i', $url)) {
            return $url;
        }

        return '';
    }

    private static function anchor(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/', '-', mb_strtolower(\Illuminate\Support\Str::ascii($value))), '-');
    }

    /** Biała lista znaczników inline dla pól tekstowych bloków; atrybuty poza bezpiecznym href znikają. */
    public static function inline(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        if (! str_contains($html, '<') && ! str_contains($html, '&')) {
            return e($html);
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="block-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('block-root');
        if (! $root) {
            return e(strip_tags($html));
        }
        self::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        // Końcowe <br> z contenteditable nie niosą treści.
        return trim((string) preg_replace('~(<br\s*/?>\s*)+$~i', '', $out));
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

            if (! in_array($tag, self::INLINE_TAGS, true)) {
                // Bloki (div/p z contenteditable) zamieniamy na złamanie wiersza, resztę rozpakowujemy.
                $isBlock = in_array($tag, ['div', 'p', 'li'], true);
                if ($isBlock && $child->previousSibling) {
                    $node->insertBefore($node->ownerDocument->createElement('br'), $child);
                }
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? self::url($child->getAttribute('href')) : '';
            $newTab = $tag === 'a' && $child->getAttribute('target') === '_blank';
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if ($href === '') {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);

                    continue;
                }
                $child->setAttribute('href', $href);
                if ($newTab) {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener');
                }
            }
        }
    }
}
