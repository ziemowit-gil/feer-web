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
    /** Najgłębsze drzewo: kontener → część (kolumna, zakładka) → zwykłe bloki. */
    public const MAX_DEPTH = 2;

    public const MAX_BLOCKS = 500;

    /**
     * Typy z zagnieżdżonymi blokami i dozwolone typy dzieci (null = dowolne nie-kontenerowe). Zagnieżdżenie ma jeden
     * poziom: w kontenerze (kolumny, zakładki, rozwijana sekcja, ramka) są tylko zwykłe bloki — kontener w kontenerze
     * zostaje przy normalizacji rozpakowany (treść zostaje, znika opakowanie). Prosta struktura = przewidywalna
     * kolejność tabulacji i nagłówków dla czytników ekranu.
     */
    private const CONTAINERS = [
        'columns' => ['column'],
        'column' => null,
        'tabs' => ['tab'],
        'tab' => null,
        'callout' => null,
        'details' => null,
    ];

    /** Części kontenerów, które istnieją tylko w swoim rodzicu: kolumna w „Kolumnach”, zakładka w „Zakładkach”. */
    private const PARTS = ['column' => 'columns', 'tab' => 'tabs'];

    /** Najwięcej części w kontenerze. */
    private const MAX_PARTS = ['columns' => 4, 'tabs' => 8];

    public const TYPES = [
        'paragraph', 'heading', 'list', 'quote', 'image', 'gallery', 'buttons', 'callout', 'partnerevent',
        'columns', 'column', 'details', 'table', 'separator', 'embed', 'file', 'html', 'shortcode', 'subpages', 'newslist', 'banner', 'imagebanner', 'materials', 'phone',
        'tabs', 'tab', 'faq', 'cta', 'people', 'cards', 'pictograms', 'faqmodule',
    ];

    private const INLINE_TAGS = ['strong', 'b', 'em', 'i', 'a', 'br', 'code', 's', 'sub', 'sup', 'mark'];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript'];

    private int $count = 0;

    /** Numer okna harmonogramu (BlockSchedule::wrap) — unikalny w obrębie jednego renderowania. */
    private int $windowSeq = 0;

    /**
     * Czyści drzewo bloków: tylko znane typy, znane atrybuty, ograniczona głębokość i liczba bloków.
     *
     * @param  array<int, mixed>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $blocks): array
    {
        return (new self)->normalizeList($blocks, false, null);
    }

    /**
     * Ile kontenerów leży w innych kontenerach (np. rozwijana sekcja w kolumnie) — normalizacja je rozpakuje,
     * a edytor mówi o tym redakcji przy otwarciu starszej treści.
     *
     * @param  array<int, mixed>  $blocks
     */
    public static function nestedContainers(array $blocks, bool $nested = false): int
    {
        $n = 0;
        foreach ($blocks as $b) {
            $type = is_array($b) ? (string) ($b['type'] ?? '') : '';
            if (! array_key_exists($type, self::CONTAINERS)) {
                continue;
            }
            if ($nested && ! isset(self::PARTS[$type])) {
                $n++;
            }
            $n += self::nestedContainers(is_array($b['inner'] ?? null) ? $b['inner'] : [], true);
        }

        return $n;
    }

    /** @param  array<int, mixed>  $blocks */
    public static function render(array $blocks): string
    {
        $renderer = new self;

        return $renderer->renderList($renderer->normalizeList($blocks, false, null));
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @param  bool  $nested  czy lista leży już w kontenerze (wtedy kontenery są rozpakowywane)
     */
    private function normalizeList(array $blocks, bool $nested, ?array $allowed): array
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
            // Kolumna / zakładka ma sens tylko w swoim rodzicu.
            $isPart = isset(self::PARTS[$type]);
            if ($isPart && $allowed === null) {
                continue;
            }
            // Kontener w kontenerze (dozwolony jest jeden poziom): zostaje treść, znika tylko opakowanie.
            if (! $isPart && $nested && array_key_exists($type, self::CONTAINERS)) {
                array_push($out, ...$this->normalizeList($this->unwrap($block), $nested, $allowed));

                continue;
            }

            $this->count++;
            $attrs = $this->normalizeAttrs($type, is_array($block['attrs'] ?? null) ? $block['attrs'] : []);
            $normalized = ['type' => $type, 'attrs' => $attrs];

            if (array_key_exists($type, self::CONTAINERS)) {
                $childAllowed = self::CONTAINERS[$type];
                $inner = is_array($block['inner'] ?? null) ? $block['inner'] : [];
                $normalized['inner'] = $this->normalizeList($inner, true, $childAllowed);
                if (isset(self::MAX_PARTS[$type])) {
                    $normalized['inner'] = array_slice($normalized['inner'], 0, self::MAX_PARTS[$type]);
                }
            }

            // Blok ukryty (karta strony → „Treść strony” → Ukryj): zostaje w blokach, ale nie trafia do HTML strony.
            if (! empty($block['hidden'])) {
                $normalized['hidden'] = true;
            }
            // Harmonogram: „Pokaż od” / „Ukryj od” (BlockSchedule) — części kontenerów go nie mają.
            if (! $isPart) {
                if ($from = BlockSchedule::clean($block['from'] ?? null)) {
                    $normalized['from'] = $from;
                }
                if ($until = BlockSchedule::clean($block['until'] ?? null)) {
                    $normalized['until'] = $until;
                }
            }

            $out[] = $normalized;
        }

        return $out;
    }

    /**
     * Treść opakowania bez niego (kontener w kontenerze): kolumny i zakładki oddają bloki
     * wszystkich części, a tytuł rozwijanej sekcji / zakładki zostaje jako pogrubiony akapit (nie nagłówek — nie
     * znamy poziomu, a przeskok poziomów psułby strukturę). Ukrycie i harmonogram opakowania przechodzą na treść.
     *
     * @return array<int, array<string, mixed>>
     */
    private function unwrap(array $block): array
    {
        $type = (string) $block['type'];
        $a = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $inner = is_array($block['inner'] ?? null) ? array_values(array_filter($block['inner'], 'is_array')) : [];
        $out = [];
        if (in_array($type, ['columns', 'tabs'], true)) {
            foreach ($inner as $part) {
                array_push($out, ...(isset(self::PARTS[$part['type'] ?? '']) ? $this->unwrap($part) : [$part]));
            }
        } else {
            $title = trim((string) match ($type) { 'details' => $a['summary'] ?? '', 'tab' => $a['label'] ?? '', default => '' });
            if ($title !== '') {
                $out[] = ['type' => 'paragraph', 'attrs' => ['text' => '<strong>'.e($title).'</strong>']];
            }
            array_push($out, ...$inner);
        }
        $inherit = array_filter(['hidden' => ! empty($block['hidden']) ? true : null, 'from' => $block['from'] ?? null, 'until' => $block['until'] ?? null]);

        return $inherit ? array_map(fn ($b) => $b + $inherit, $out) : $out;
    }

    /** @return array<string, mixed> */
    private function normalizeAttrs(string $type, array $a): array
    {
        $str = fn (string $key, int $max = 2000) => mb_substr(trim((string) ($a[$key] ?? '')), 0, $max);
        $rich = fn (string $key, int $max = 20000) => self::inline(mb_substr((string) ($a[$key] ?? ''), 0, $max));
        $pick = fn (string $key, array $options, string $default) => in_array($a[$key] ?? null, $options, true) ? $a[$key] : $default;
        $align = fn () => $pick('align', ['left', 'center', 'right'], 'left');
        $line = fn (string $key, int $max) => self::line($a[$key] ?? '', $max);

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
            'partnerevent' => ['text' => $rich('text')],
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
            'phone' => ['label' => $str('label', 120), 'number' => preg_replace('/[^0-9 +()\-.]/', '', $str('number', 40)), 'note' => $str('note', 160)],
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
            'materials' => MaterialList::normalize($a),
            'faqmodule' => FaqSection::normalize($a),
            'tabs' => ['level' => max(2, min(4, (int) ($a['level'] ?? 2)))],
            'tab' => ['label' => $line('label', 120)],
            'faq' => [
                'title' => $line('title', 200),
                'level' => max(2, min(4, (int) ($a['level'] ?? 2))),
                'items' => self::items($a, 50, fn ($it) => [
                    'q' => self::line($it['q'] ?? '', 300),
                    'a' => self::inline(mb_substr((string) ($it['a'] ?? ''), 0, 5000)),
                ], 'q'),
            ],
            'cta' => [
                'title' => $line('title', 200),
                'level' => max(2, min(4, (int) ($a['level'] ?? 2))),
                'text' => $rich('text', 3000),
                'label' => $line('label', 120),
                'url' => self::url($str('url')),
                'newTab' => (bool) ($a['newTab'] ?? false),
                'tone' => $pick('tone', ['light', 'gray'], 'light'),
            ],
            'people' => [
                'level' => max(2, min(4, (int) ($a['level'] ?? 3))),
                'columns' => max(2, min(4, (int) ($a['columns'] ?? 3))),
                'items' => self::items($a, 30, fn ($it) => [
                    'name' => self::line($it['name'] ?? '', 120),
                    'role' => self::line($it['role'] ?? '', 160),
                    'photo' => self::url(mb_substr((string) ($it['photo'] ?? ''), 0, 2000)),
                    'email' => filter_var(trim((string) ($it['email'] ?? '')), FILTER_VALIDATE_EMAIL) ? mb_substr(trim((string) $it['email']), 0, 160) : '',
                    'phone' => preg_replace('/[^0-9 +()\-.]/', '', self::line($it['phone'] ?? '', 40)),
                    'bio' => self::line($it['bio'] ?? '', 600),
                ], 'name'),
            ],
            'cards' => [
                'level' => max(2, min(4, (int) ($a['level'] ?? 3))),
                'columns' => max(2, min(4, (int) ($a['columns'] ?? 3))),
                'items' => self::items($a, 24, fn ($it) => [
                    'title' => self::line($it['title'] ?? '', 160),
                    'text' => self::line($it['text'] ?? '', 600),
                    'image' => self::url(mb_substr((string) ($it['image'] ?? ''), 0, 2000)),
                    'alt' => self::line($it['alt'] ?? '', 300),
                    'url' => self::url(mb_substr((string) ($it['url'] ?? ''), 0, 2000)),
                ], 'title'),
            ],
            'pictograms' => [
                'title' => $line('title', 200),
                'level' => max(2, min(4, (int) ($a['level'] ?? 2))),
                'labels' => (bool) ($a['labels'] ?? true),
                'size' => $pick('size', array_keys(AccessibilityPictograms::SIZES), 'medium'),
                'items' => AccessibilityPictograms::normalizeItems($a['items'] ?? []),
            ],
            'shortcode' => ['code' => preg_match('/^\[[a-z0-9_-]+(?::[^\]\r\n]{1,200})?\]$/i', $str('code', 220)) ? $str('code', 220) : ''],
            default => [],
        };
    }

    /** Jednowierszowy tekst bez znaczników (białe znaki zwinięte do spacji). */
    private static function line(mixed $value, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', strip_tags(is_scalar($value) ? (string) $value : ''))), 0, $max);
    }

    /**
     * Lista elementów bloku typowanego (FAQ, osoby, karty): najwyżej $max, każdy przez $map, bez elementów
     * z pustym polem $required (np. pytanie bez treści).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function items(array $a, int $max, callable $map, string $required): array
    {
        $items = array_slice(array_values(array_filter(is_array($a['items'] ?? null) ? $a['items'] : [], 'is_array')), 0, $max);

        return array_values(array_filter(array_map($map, $items), fn ($it) => $it[$required] !== ''));
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
        return implode("\n", array_filter(array_map(function ($b) {
            if (! empty($b['hidden'])) {
                return '';
            }
            $html = $this->renderBlock($b);
            // Blok z harmonogramem: o widoczności decyduje BlockSchedule::filter() przy wyświetlaniu strony.
            if ($html !== '' && (isset($b['from']) || isset($b['until']))) {
                $html = BlockSchedule::wrap($html, $b['from'] ?? null, $b['until'] ?? null, ++$this->windowSeq);
            }

            return $html;
        }, $blocks), fn ($html) => $html !== ''));
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

            case 'partnerevent':
                return '<div class="partner-event-box"><p class="partner-event-tag">Materiał partnera – innej organizacji</p><p>'.$a['text'].'</p></div>';

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

            case 'tabs':
                return $this->renderTabs($a, $b['inner'] ?? []);

            case 'tab':
                return $inner();

            case 'faq':
                return $this->renderFaq($a);

            case 'cta':
                return $this->renderCta($a);

            case 'people':
                return $this->renderPeople($a);

            case 'cards':
                return $this->renderCards($a);

            case 'pictograms':
                return AccessibilityPictograms::render($a['items'], $a['labels'], $a['size'], $a['title'], $a['level']);

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

            case 'phone':
                $digits = preg_replace('/[^0-9+]/', '', $a['number']);
                if ($digits === '') {
                    return '';
                }
                $label = $a['label'] !== '' ? '<p class="phone-reveal-label">'.e($a['label']).'</p>' : '';
                $note = $a['note'] !== '' ? '<p class="phone-reveal-note">'.e($a['note']).'</p>' : '';

                // Bez JS numer jest widoczny od razu; skrypt (resources/js/app.js) ukrywa go za przyciskiem do czasu najechania, fokusu albo kliknięcia.
                return '<div class="phone-reveal" data-phone-reveal><span class="phone-reveal-icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>'
                    .'<div class="phone-reveal-body">'.$label
                    .'<button type="button" class="phone-reveal-btn" aria-expanded="false"><span>Pokaż numer telefonu</span></button>'
                    .'<a class="phone-reveal-number" href="tel:'.e($digits).'">'.e($a['number']).'</a>'
                    .$note.'</div></div>';

            case 'file':
                if ($a['url'] === '') {
                    return '';
                }
                $ext = strtoupper(pathinfo(parse_url($a['url'], PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                $label = $a['label'] !== '' ? $a['label'] : basename(parse_url($a['url'], PHP_URL_PATH) ?: $a['url']);
                $desc = $a['description'] !== '' ? '<span class="content-file-desc">'.e($a['description']).'</span>' : '';
                // Rodzaj i rozmiar pliku w nazwie linku (np. „Regulamin (PDF, 690,5 KB)”) — rozmiar tylko dla plików z naszej biblioteki.
                $meta = implode(', ', array_filter([$ext, self::localFileSize($a['url'])]));

                return '<p class="content-file"><a href="'.e($a['url']).'" download>'.e($label)
                    .($meta !== '' ? ' <span class="content-file-meta">('.e($meta).')</span>' : '').'</a>'.$desc.'</p>';

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

            case 'materials':
                return '<p>'.MaterialList::shortcode($a).'</p>';

            case 'faqmodule':
                return '<p>'.e(FaqSection::shortcode($a)).'</p>';
        }

        return '';
    }

    /**
     * Zakładki: bez skryptu każda zakładka to sekcja z nagłówkiem (czytelna jedna pod drugą); skrypt frontu
     * (resources/js/app.js, [data-tabs]) robi z nich listę kart ARIA z obsługą strzałek, Home i End.
     *
     * @param  array<string, mixed>  $a
     * @param  array<int, array<string, mixed>>  $tabs
     */
    private function renderTabs(array $a, array $tabs): string
    {
        $tag = 'h'.$a['level'];
        $html = '';
        foreach (array_values($tabs) as $i => $tab) {
            $label = ($tab['attrs']['label'] ?? '') !== '' ? $tab['attrs']['label'] : 'Zakładka '.($i + 1);
            $html .= '<section class="content-tab"><'.$tag.' class="content-tab-title">'.e($label).'</'.$tag.'>'
                .'<div class="content-tab-body">'.$this->renderList($tab['inner'] ?? []).'</div></section>';
        }

        return $html !== '' ? '<div class="content-tabs" data-tabs>'.$html.'</div>' : '';
    }

    /**
     * Pytania i odpowiedzi: opcjonalny nagłówek sekcji i pytania jako rozwijane sekcje (te same style co „Rozwijana
     * sekcja”). Pusta linia w odpowiedzi zaczyna nowy akapit.
     *
     * @param  array<string, mixed>  $a
     */
    private function renderFaq(array $a): string
    {
        if ($a['items'] === []) {
            return '';
        }
        $tag = 'h'.$a['level'];
        $items = array_map(function ($it) {
            $paras = array_filter(preg_split('~(?:<br\s*/?>\s*){2,}~i', $it['a']) ?: [], fn ($p) => trim(strip_tags($p)) !== '');

            return '<details class="content-details"><summary>'.e($it['q']).'</summary><div class="content-details-body">'
                .implode('', array_map(fn ($p) => '<p>'.trim($p).'</p>', $paras)).'</div></details>';
        }, $a['items']);

        return '<div class="content-faq">'.($a['title'] !== '' ? "<{$tag} class=\"content-faq-title\">".e($a['title'])."</{$tag}>" : '')
            .implode('', $items).'</div>';
    }

    /**
     * Wezwanie do działania: nagłówek, krótki tekst i jeden przycisk na jasnym tle (bez ramek; tekst ink, kontrast ≥ 12:1).
     *
     * @param  array<string, mixed>  $a
     */
    private function renderCta(array $a): string
    {
        $hasText = trim(strip_tags($a['text'])) !== '';
        $hasButton = $a['label'] !== '' && $a['url'] !== '';
        if ($a['title'] === '' && ! $hasText && ! $hasButton) {
            return '';
        }
        $tag = 'h'.$a['level'];
        $button = '';
        if ($hasButton) {
            $target = $a['newTab'] ? ' target="_blank" rel="noopener"' : '';
            $sr = $a['newTab'] ? '<span class="sr-only"> (otwiera się w nowej karcie)</span>' : '';
            $button = '<p class="content-cta-action"><a href="'.e($a['url']).'" class="cta-button"'.$target.'>'.e($a['label']).$sr.'</a></p>';
        }

        return '<div class="content-cta content-cta--'.$a['tone'].'">'
            .($a['title'] !== '' ? "<{$tag} class=\"content-cta-title\">".e($a['title'])."</{$tag}>" : '')
            .($hasText ? '<p class="content-cta-text">'.$a['text'].'</p>' : '')
            .$button.'</div>';
    }

    /**
     * Zespół / osoby do kontaktu: lista kart (zdjęcie, imię i nazwisko jako nagłówek, funkcja, opis, e-mail, telefon).
     * Zdjęcie ma pusty alt — osobę nazywa nagłówek tuż obok. role="list", bo style list zdejmują semantykę w Safari.
     *
     * @param  array<string, mixed>  $a
     */
    private function renderPeople(array $a): string
    {
        if ($a['items'] === []) {
            return '';
        }
        $tag = 'h'.$a['level'];
        $cards = array_map(function ($p) use ($tag) {
            $contact = array_filter([
                $p['email'] !== '' ? '<a href="mailto:'.e($p['email']).'">'.e($p['email']).'</a>' : null,
                $p['phone'] !== '' ? '<a href="tel:'.e(preg_replace('/[^0-9+]/', '', $p['phone'])).'">'.e($p['phone']).'</a>' : null,
            ]);

            return '<li class="content-person">'
                .($p['photo'] !== '' ? '<img class="content-person-photo" src="'.e($p['photo']).'" alt="" loading="lazy">' : '')
                .'<div class="content-person-body">'
                ."<{$tag} class=\"content-person-name\">".e($p['name'])."</{$tag}>"
                .($p['role'] !== '' ? '<p class="content-person-role">'.e($p['role']).'</p>' : '')
                .($p['bio'] !== '' ? '<p class="content-person-bio">'.e($p['bio']).'</p>' : '')
                .($contact ? '<p class="content-person-contact">'.implode('<br>', $contact).'</p>' : '')
                .'</div></li>';
        }, $a['items']);

        return '<ul class="content-people content-people--cols-'.$a['columns'].'" role="list">'.implode('', $cards).'</ul>';
    }

    /**
     * Siatka kart: tytuł (nagłówek, opcjonalnie link na całą kartę), krótki opis i grafika. W kodzie grafika jest po
     * tekście (czytnik zaczyna od tytułu), na ekranie nad nim (CSS order). Bez opisu grafika jest dekoracyjna.
     *
     * @param  array<string, mixed>  $a
     */
    private function renderCards(array $a): string
    {
        if ($a['items'] === []) {
            return '';
        }
        $tag = 'h'.$a['level'];
        $cards = array_map(fn ($c) => '<li class="content-card">'
            .'<div class="content-card-body">'
            ."<{$tag} class=\"content-card-title\">".($c['url'] !== '' ? '<a href="'.e($c['url']).'" class="content-card-link">'.e($c['title']).'</a>' : e($c['title']))."</{$tag}>"
            .($c['text'] !== '' ? '<p class="content-card-text">'.e($c['text']).'</p>' : '')
            .'</div>'
            .($c['image'] !== '' ? '<img class="content-card-image" src="'.e($c['image']).'" alt="'.e($c['alt']).'" loading="lazy">' : '')
            .'</li>', $a['items']);

        return '<ul class="content-cards content-cards--cols-'.$a['columns'].'" role="list">'.implode('', $cards).'</ul>';
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

    /**
     * Rozmiar pliku z naszej biblioteki (adres /storage/… albo plik w public/) w zapisie „690,5 KB”; dla adresów
     * zewnętrznych i brakujących plików null. Liczony przy zapisie treści (bloki zamieniają się wtedy w HTML).
     */
    public static function localFileSize(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== null && $host !== parse_url(url('/'), PHP_URL_HOST)) {
            return null;
        }
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }
        $path = rawurldecode($path);
        $candidates = str_starts_with($path, 'storage/')
            ? [storage_path('app/public/'.substr($path, strlen('storage/'))), public_path($path)]
            : [public_path($path)];
        foreach ($candidates as $file) {
            if (is_file($file) && ($bytes = @filesize($file)) !== false) {
                return self::humanSize($bytes);
            }
        }

        return null;
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        $i = 0;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return str_replace('.', ',', (string) round($value, 1)).' '.$units[$i];
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
