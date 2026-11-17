<?php

namespace App\Support;

/**
 * Krótki opis bloku edytora blokowego do „Treści strony” w karcie (Strony Nawigator, jak Page module w TYPO3):
 * nazwa typu po polsku, ikona i fragment tekstu. Do tego informacja, czy blok da się poprawić w karcie
 * (szybka edycja: nagłówek, akapit i lista bez formatowania), czy tylko w pełnym edytorze.
 */
class BlockSummary
{
    /** typ => [nazwa, ikona Font Awesome] */
    public const TYPES = [
        'paragraph' => ['Akapit', 'fa-paragraph'],
        'heading' => ['Nagłówek', 'fa-heading'],
        'list' => ['Lista', 'fa-list-ul'],
        'quote' => ['Cytat', 'fa-quote-left'],
        'image' => ['Obraz', 'fa-image'],
        'gallery' => ['Galeria', 'fa-images'],
        'buttons' => ['Przyciski', 'fa-hand-pointer'],
        'callout' => ['Ramka', 'fa-square'],
        'partnerevent' => ['Materiał partnera', 'fa-handshake'],
        'columns' => ['Kolumny', 'fa-table-columns'],
        'details' => ['Rozwijana sekcja', 'fa-caret-down'],
        'table' => ['Tabela', 'fa-table'],
        'separator' => ['Linia oddzielająca', 'fa-minus'],
        'embed' => ['Osadzenie', 'fa-photo-film'],
        'file' => ['Plik', 'fa-file-arrow-down'],
        'html' => ['Kod HTML', 'fa-code'],
        'shortcode' => ['Wstawka', 'fa-puzzle-piece'],
        'subpages' => ['Lista podstron', 'fa-sitemap'],
        'newslist' => ['Lista aktualności', 'fa-newspaper'],
        'banner' => ['Baner', 'fa-panorama'],
        'imagebanner' => ['Baner graficzny', 'fa-rectangle-ad'],
        'materials' => ['Lista materiałów', 'fa-book-open'],
        'phone' => ['Telefon', 'fa-phone'],
    ];

    /** Znaczniki, które szybka edycja zachowuje bez utraty informacji (zwykły tekst z podziałem wierszy). */
    private const PLAIN_TAGS = '<br>';

    /** @return array{label:string, icon:string, text:string, editable:bool} */
    public static function describe(array $block): array
    {
        $type = (string) ($block['type'] ?? '');
        $a = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        [$label, $icon] = self::TYPES[$type] ?? [$type, 'fa-cube'];
        $inner = is_array($block['inner'] ?? null) ? $block['inner'] : [];

        $text = match ($type) {
            'paragraph', 'quote', 'partnerevent' => self::plain($a['text'] ?? ''),
            'heading' => 'H'.((int) ($a['level'] ?? 2)).': '.self::plain($a['text'] ?? ''),
            'list' => implode(' · ', array_map([self::class, 'plain'], array_slice($a['items'] ?? [], 0, 4))).(count($a['items'] ?? []) > 4 ? ' …' : ''),
            'image' => ($a['decorative'] ?? false) ? 'ozdobny: '.basename((string) ($a['url'] ?? '')) : ((string) ($a['alt'] ?? '') ?: basename((string) ($a['url'] ?? ''))),
            'gallery' => count($a['images'] ?? []).' '.self::plural(count($a['images'] ?? []), 'zdjęcie', 'zdjęcia', 'zdjęć'),
            'buttons' => implode(' · ', array_map(fn ($b) => (string) ($b['label'] ?? ''), $a['items'] ?? [])),
            'table' => ($a['caption'] ?? '') !== '' ? $a['caption'] : count($a['rows'] ?? []).' '.self::plural(count($a['rows'] ?? []), 'wiersz', 'wiersze', 'wierszy'),
            'details' => (string) ($a['summary'] ?? ''),
            'embed' => (string) (($a['title'] ?? '') ?: ($a['url'] ?? '')),
            'file' => (string) (($a['label'] ?? '') ?: basename((string) ($a['url'] ?? ''))),
            'banner' => (string) ($a['title'] ?? ''),
            'imagebanner' => (string) ($a['alt'] ?? ''),
            'phone' => trim(($a['label'] ?? '').' '.($a['number'] ?? '')),
            'shortcode' => (string) ($a['code'] ?? ''),
            'columns', 'callout' => count($inner).' '.self::plural(count($inner), 'element', 'elementy', 'elementów').': '
                .implode(' · ', array_map(fn ($b) => self::describe($b)['label'], array_slice(self::flatten($inner), 0, 4))),
            default => '',
        };

        return [
            'label' => $label,
            'icon' => $icon,
            'text' => mb_strimwidth(trim(preg_replace('/\s+/u', ' ', $text)), 0, 140, '…'),
            'editable' => self::quickEditable($block),
        ];
    }

    /** Szybka edycja w karcie: nagłówek, akapit i lista, o ile nie mają formatowania (linków, pogrubień), które by zginęło. */
    public static function quickEditable(array $block): bool
    {
        $a = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];

        return match ($block['type'] ?? '') {
            'heading', 'paragraph' => self::isPlain((string) ($a['text'] ?? '')),
            'list' => collect($a['items'] ?? [])->every(fn ($i) => self::isPlain((string) $i)),
            default => false,
        };
    }

    /** Tekst do pola szybkiej edycji (bez znaczników; <br> → nowy wiersz). */
    public static function editText(array $block): string
    {
        $a = $block['attrs'] ?? [];

        return ($block['type'] ?? '') === 'list'
            ? implode("\n", array_map(fn ($i) => self::decode((string) $i), $a['items'] ?? []))
            : self::decode((string) ($a['text'] ?? ''));
    }

    private static function isPlain(string $html): bool
    {
        return strip_tags($html, self::PLAIN_TAGS) === $html;
    }

    private static function decode(string $html): string
    {
        return html_entity_decode(preg_replace('#<br\s*/?>#i', "\n", $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function plain(mixed $html): string
    {
        return html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @return array<int, array<string, mixed>> bloki z kolumn spłaszczone o jeden poziom (kolumna → jej bloki). */
    private static function flatten(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (($b['type'] ?? '') === 'column') {
                array_push($out, ...($b['inner'] ?? []));
            } else {
                $out[] = $b;
            }
        }

        return $out;
    }

    private static function plural(int $n, string $one, string $few, string $many): string
    {
        if ($n === 1) {
            return $one;
        }

        return ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? $few : $many;
    }
}
