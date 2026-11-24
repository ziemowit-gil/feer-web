<?php

namespace App\Support;

/**
 * Piktogramy dostępności (audiodeskrypcja, napisy, pętla indukcyjna, PJM, ETR…) z public/img/piktogramy-dostepnosci.
 *
 * Autor: Tomasz Grabowski, prawa autorskie: Fundacja Kultury Bez Barier, licencja CC BY-SA 4.0
 * (źródło: github.com/JacZad/piktogramy-dostepnosci). Licencja wymaga podpisu przy publikacji: stoi on pod stopką
 * każdego szablonu (partials/pictograms-credit, {@see attribution()}), bo piktogramy są też w ikonach modułów
 * dostępności (klasy .picto-icon .picto-{klucz} w app.css, kolor z currentColor przez maskę CSS).
 *
 * Używają ich blok edytora „Piktogramy dostępności” (BlockRenderer, typ `pictograms`) i wstawka klasycznego
 * edytora `[piktogramy:etr,audiodeskrypcja]` (ShortcodeParser).
 *
 * Dostępność: przy widocznym podpisie obrazek ma alt="" (nazwę czyta się raz, z podpisu); bez podpisów nazwa
 * piktogramu (albo własna etykieta) trafia do alt.
 */
class AccessibilityPictograms
{
    public const DIR = '/img/piktogramy-dostepnosci/';

    /** Klucz (nazwa pliku bez .svg) => nazwa piktogramu = tekst alternatywny. */
    public const ALL = [
        'dostepnosc' => 'Dostępność',
        'dostepnosc-dla-osob-z-niepelnosprawnoscia-ruchowa' => 'Dostępność dla osób z niepełnosprawnością ruchową',
        'dostepnosc-dla-osob-z-niepelnosprawnoscia-intelektualna' => 'Dostępność dla osób z niepełnosprawnością intelektualną',
        'dostepnosc-dla-osob-w-spektrum-autyzmu' => 'Dostępność dla osób w spektrum autyzmu',
        'audiodeskrypcja' => 'Audiodeskrypcja',
        'audioprzewodnik' => 'Audioprzewodnik',
        'przedprzewodnik' => 'Przedprzewodnik',
        'materialy-dotykowe' => 'Materiały dotykowe',
        'etr' => 'Tekst łatwy do czytania (ETR)',
        'tlumaczenie-na-pjm' => 'Tłumaczenie na PJM',
        'prowadzenie-w-pjm' => 'Prowadzenie w PJM',
        'tlumaczenie-na-jezyk-obcy' => 'Tłumaczenie na język obcy',
        'lektor-polski' => 'Lektor polski',
        'napisy-polskie' => 'Napisy polskie',
        'napisy-angielskie' => 'Napisy angielskie',
        'napisy-dla-nieslyszacych' => 'Napisy dla niesłyszących',
        'napisy-na-zywo' => 'Napisy na żywo',
        'petla-indukcyjna' => 'Pętla indukcyjna',
        'system-fm' => 'System FM',
        'miejsce-wyciszenia' => 'Miejsce wyciszenia',
        'ciche-godziny' => 'Ciche godziny',
        'sluchawki-wyciszajace' => 'Słuchawki wyciszające',
    ];

    /** Rozmiar => bok obrazka w pikselach. */
    public const SIZES = ['small' => 32, 'medium' => 48, 'large' => 64];

    public const MAX_ITEMS = 22;

    public static function url(string $key): string
    {
        return self::DIR.$key.'.svg';
    }

    /**
     * Lista do edytora blokowego: klucz, nazwa, adres pliku.
     *
     * @return array<int, array{key:string, name:string, url:string}>
     */
    public static function catalog(): array
    {
        return array_map(fn ($key, $name) => ['key' => $key, 'name' => $name, 'url' => self::url($key)], array_keys(self::ALL), self::ALL);
    }

    /**
     * Pozycje zestawu: tylko znane klucze, bez powtórzeń, z własną etykietą (pusta = nazwa piktogramu).
     *
     * @return array<int, array{key:string, label:string}>
     */
    public static function normalizeItems(mixed $items): array
    {
        $out = [];
        foreach (is_array($items) ? $items : [] as $item) {
            $key = is_array($item) ? (string) ($item['key'] ?? '') : (is_string($item) ? $item : '');
            $key = strtolower(trim($key));
            if (! isset(self::ALL[$key]) || isset($out[$key])) {
                continue;
            }
            $label = is_array($item) ? trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($item['label'] ?? '')))) : '';
            $out[$key] = ['key' => $key, 'label' => mb_substr($label, 0, 160)];
            if (count($out) >= self::MAX_ITEMS) {
                break;
            }
        }

        return array_values($out);
    }

    /**
     * HTML zestawu piktogramów z podpisem autorstwa. Pusty zestaw = pusty tekst.
     *
     * @param  array<int, array{key:string, label:string}>  $items
     */
    public static function render(array $items, bool $labels = true, string $size = 'medium', string $title = '', int $level = 2): string
    {
        $items = self::normalizeItems($items);
        if ($items === []) {
            return '';
        }
        $size = isset(self::SIZES[$size]) ? $size : 'medium';
        $px = self::SIZES[$size];
        $level = max(2, min(4, $level));

        $list = implode('', array_map(function ($it) use ($labels, $px) {
            $name = $it['label'] !== '' ? $it['label'] : self::ALL[$it['key']];
            $img = '<img class="content-pictogram-image" src="'.e(self::url($it['key'])).'" alt="'.($labels ? '' : e($name)).'"'
                .' width="'.$px.'" height="'.$px.'" loading="lazy" decoding="async">';

            return '<li class="content-pictogram">'.$img.($labels ? '<span class="content-pictogram-label">'.e($name).'</span>' : '').'</li>';
        }, $items));

        $heading = trim($title) !== '' ? "<h{$level} class=\"content-pictograms-title\">".e(trim($title))."</h{$level}>" : '';

        return '<div class="content-pictograms content-pictograms--'.$size.($labels ? '' : ' content-pictograms--icons').'">'
            .$heading.'<ul class="content-pictograms-list" role="list">'.$list.'</ul></div>';
    }

    /** Podpis wymagany przez licencję CC BY-SA 4.0 (pod stopką: partials/pictograms-credit). */
    public static function attribution(): string
    {
        return 'Piktogramy: Tomasz Grabowski, prawa autorskie: <a href="https://kulturabezbarier.org/">Fundacja Kultury Bez Barier</a>,'
            .' licencja <a href="https://creativecommons.org/licenses/by-sa/4.0/legalcode.pl">CC BY-SA 4.0</a>.';
    }

    /**
     * Wstawka `[piktogramy:etr,audiodeskrypcja]` (klucze po przecinku; `bez-podpisow` na końcu = same ikony z alt).
     */
    public static function renderShortcode(string $args): string
    {
        $keys = array_values(array_filter(array_map(fn ($k) => strtolower(trim($k)), explode(',', $args)), fn ($k) => $k !== ''));
        $labels = ! in_array('bez-podpisow', $keys, true);

        return self::render(array_diff($keys, ['bez-podpisow']), $labels);
    }
}
