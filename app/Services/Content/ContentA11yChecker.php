<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Sprawdzanie dostępności treści (HTML z edytora) — przy zapisie strony i aktualności,
 * w panelu bocznym edycji i w raporcie dla całego serwisu.
 *
 * Wyłapuje to, co redaktor może poprawić sam, a co najczęściej przeszkadza osobom
 * korzystającym z czytników ekranu (WCAG 1.1.1, 1.3.1, 2.4.4, 2.4.6, 4.1.2):
 * kolejność nagłówków, „kliknij tutaj”, linki bez tekstu, obrazy bez opisu,
 * tabele bez nagłówków, osadzenia bez tytułu, pogrubienia udające nagłówki,
 * listy pisane myślnikami.
 *
 * Każda uwaga: ['rule', 'level' (error|warning), 'message', 'hint', 'excerpt'].
 * Błąd (error) wyklucza część odbiorców; uwaga (warning) utrudnia korzystanie.
 */
class ContentA11yChecker
{
    /** Teksty linków, które poza kontekstem nic nie mówią (porównanie po normalizacji). */
    private const VAGUE_LINKS = [
        'kliknij', 'kliknij tutaj', 'kliknij tu', 'tutaj', 'tu', 'więcej', 'czytaj więcej', 'czytaj dalej',
        'zobacz', 'zobacz więcej', 'dowiedz się więcej', 'link', 'ten link', 'pod tym linkiem', 'pobierz',
        'strona', 'szczegóły', 'here', 'click here', 'read more', 'more', 'link here',
    ];

    public const RULES = [
        'heading_h1' => 'Nagłówek H1 w treści',
        'heading_first' => 'Treść nie zaczyna się od H2',
        'heading_skip' => 'Przeskok poziomu nagłówka',
        'heading_empty' => 'Pusty nagłówek',
        'fake_heading' => 'Pogrubienie zamiast nagłówka',
        'link_vague' => 'Niejasny tekst linku',
        'link_empty' => 'Link bez tekstu',
        'link_url' => 'Adres zamiast tekstu linku',
        'img_alt_missing' => 'Obraz bez opisu alternatywnego',
        'img_alt_filename' => 'Nazwa pliku jako opis obrazu',
        'table_header' => 'Tabela bez nagłówków',
        'iframe_title' => 'Osadzenie bez tytułu',
        'fake_list' => 'Lista pisana myślnikami',
    ];

    /**
     * @param  int  $titleLevel  poziom nagłówka tytułu strony (domyślnie H1 — treść zaczyna się od H2)
     * @return array<int, array{rule: string, level: string, message: string, hint: string, excerpt: ?string}>
     */
    public function check(?string $html, int $titleLevel = 1): array
    {
        $html = trim((string) $html);
        if ($html === '' || trim(strip_tags($html, '<img><iframe><table>')) === '') {
            return [];
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="a11y-root">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="a11y-root"]')->item(0);
        if (! $root) {
            return [];
        }

        return array_merge(
            $this->headings($xpath, $root, $titleLevel),
            $this->fakeHeadings($xpath, $root),
            $this->links($xpath, $root),
            $this->images($xpath, $root),
            $this->tables($xpath, $root),
            $this->iframes($xpath, $root),
            $this->fakeLists($xpath, $root),
        );
    }

    /**
     * Dopisek do komunikatu po zapisie treści („Dostępność treści: 2 błędy…”) albo pusty tekst.
     * Zapis nie jest blokowany — redaktor dostaje informację i szczegóły w panelu edycji.
     */
    public static function saveNotice(?string $html): string
    {
        $summary = self::summary(app(self::class)->check($html));
        if (! $summary['errors'] && ! $summary['warnings']) {
            return '';
        }

        $parts = array_filter([
            $summary['errors'] ? $summary['errors'].' '.trans_choice('błąd|błędy|błędów', $summary['errors']) : null,
            $summary['warnings'] ? $summary['warnings'].' '.trans_choice('uwaga|uwagi|uwag', $summary['warnings']) : null,
        ]);

        return ' Dostępność treści: '.implode(' i ', $parts).' — szczegóły w panelu „Dostępność treści” w edycji.';
    }

    /** Liczba błędów i uwag. */
    public static function summary(array $issues): array
    {
        $levels = array_count_values(array_column($issues, 'level'));

        return ['errors' => $levels['error'] ?? 0, 'warnings' => $levels['warning'] ?? 0];
    }

    private function headings(DOMXPath $xpath, DOMElement $root, int $titleLevel): array
    {
        $issues = [];
        $last = $titleLevel;
        $first = true;

        foreach ($xpath->query('.//h1|.//h2|.//h3|.//h4|.//h5|.//h6', $root) as $h) {
            $level = (int) substr($h->nodeName, 1);
            $text = $this->text($h);

            if ($text === '') {
                $issues[] = $this->issue('heading_empty', 'error', 'Pusty nagłówek '.strtoupper($h->nodeName).'.',
                    'Usuń pusty nagłówek albo wpisz jego treść — czytnik ekranu ogłasza go jako sekcję bez nazwy.');

                continue;
            }

            if ($level <= $titleLevel) {
                $issues[] = $this->issue('heading_h1', 'warning', 'Nagłówek „'.$this->short($text).'” ma poziom H'.$level.' — taki sam jak tytuł strony.',
                    'Tytuł strony jest już nagłówkiem H'.$titleLevel.'. Śródtytuły w treści zaczynaj od H'.($titleLevel + 1).'.', $text);
            } elseif ($first && $level > $titleLevel + 1) {
                $issues[] = $this->issue('heading_first', 'warning', 'Pierwszy nagłówek „'.$this->short($text).'” to H'.$level.'.',
                    'Zacznij od H'.($titleLevel + 1).' (w edytorze: „Nagłówek 2”) — wyższe poziomy dla podsekcji.', $text);
            } elseif (! $first && $level > $last + 1) {
                $issues[] = $this->issue('heading_skip', 'error', 'Po H'.$last.' jest od razu H'.$level.' („'.$this->short($text).'”).',
                    'Nie pomijaj poziomów: po H'.$last.' użyj H'.($last + 1).'. Poziom nagłówka oznacza strukturę, nie wielkość tekstu.', $text);
            }

            $last = max($level, $titleLevel);
            $first = false;
        }

        return $issues;
    }

    /** Akapit, którego cała treść to krótkie pogrubienie — wygląda jak nagłówek, ale nim nie jest. */
    private function fakeHeadings(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        foreach ($xpath->query('.//p', $root) as $p) {
            $text = $this->text($p);
            if ($text === '' || mb_strlen($text) > 80 || preg_match('/[.:;!?,]$/u', $text)) {
                continue;
            }
            $bold = '';
            foreach ($xpath->query('./strong|./b', $p) as $b) {
                $bold .= $this->text($b);
            }
            if ($bold !== '' && $this->normalize($bold) === $this->normalize($text) && $p->nextSibling) {
                $issues[] = $this->issue('fake_heading', 'warning', 'Pogrubiony akapit „'.$this->short($text).'” wygląda jak śródtytuł.',
                    'Jeśli to tytuł sekcji, ustaw styl „Nagłówek 2” (albo 3) — dzięki temu czytnik ekranu pozwoli do niego przeskoczyć.', $text);
            }
        }

        return $issues;
    }

    private function links(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        foreach ($xpath->query('.//a[@href]', $root) as $a) {
            $label = $this->accessibleName($xpath, $a);
            $href = trim($a->getAttribute('href'));

            if ($label === '') {
                $issues[] = $this->issue('link_empty', 'error', 'Link do „'.$this->short($href, 60).'” nie ma tekstu.',
                    'Dodaj tekst linku (albo opis obrazu w linku) — inaczej czytnik ekranu przeczyta sam adres albo nic.', $href);

                continue;
            }

            $normalized = $this->normalize($label);
            if (in_array($normalized, self::VAGUE_LINKS, true)) {
                $issues[] = $this->issue('link_vague', 'warning', 'Link „'.$label.'” nie mówi, dokąd prowadzi.',
                    'Napisz w linku cel, np. „Regulamin konkursu (PDF)” zamiast „kliknij tutaj”. Osoby niewidome często przeglądają same linki, bez otaczającego tekstu.', $label);
            } elseif (preg_match('#^(https?://|www\.)\S{30,}$#i', $label)) {
                $issues[] = $this->issue('link_url', 'warning', 'Tekstem linku jest długi adres „'.$this->short($label, 60).'”.',
                    'Zamień adres na opis celu, np. „Formularz zgłoszeniowy”. Czytnik ekranu czyta adres znak po znaku.', $label);
            }
        }

        return $issues;
    }

    private function images(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        foreach ($xpath->query('.//img', $root) as $img) {
            $src = $img->getAttribute('src');
            $name = basename(parse_url($src, PHP_URL_PATH) ?: $src);

            if (! $img->hasAttribute('alt')) {
                $issues[] = $this->issue('img_alt_missing', 'error', 'Obraz „'.$this->short($name, 60).'” nie ma opisu alternatywnego.',
                    'Opisz, co przedstawia obraz (np. „Wolontariusze pakują paczki”). Jeśli obraz jest tylko ozdobą, zaznacz go jako dekoracyjny (pusty opis).', $name);

                continue;
            }

            $alt = trim($img->getAttribute('alt'));
            if ($alt !== '' && (preg_match('/\.(jpe?g|png|gif|webp|svg|avif)$/i', $alt) || preg_match('/^(img|dsc|image|photo|zdj[eę]cie|obraz)[\s_\-]*\d*$/iu', $alt))) {
                $issues[] = $this->issue('img_alt_filename', 'warning', 'Opis obrazu to „'.$this->short($alt, 60).'”.',
                    'Opis powinien mówić, co jest na obrazie, a nie podawać nazwę pliku czy słowo „zdjęcie”.', $alt);
            }
        }

        return $issues;
    }

    private function tables(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        foreach ($xpath->query('.//table', $root) as $i => $table) {
            if ($xpath->query('.//th', $table)->length === 0) {
                $first = $this->text($xpath->query('.//td', $table)->item(0) ?? $table);
                $issues[] = $this->issue('table_header', 'error', 'Tabela '.($i + 1).($first !== '' ? ' (zaczyna się od „'.$this->short($first, 40).'”)' : '').' nie ma komórek nagłówka.',
                    'Oznacz pierwszy wiersz (lub kolumnę) jako nagłówek tabeli. Bez tego czytnik ekranu nie powie, do czego odnosi się wartość w komórce. Jeśli to nie dane, tylko układ — zamiast tabeli użyj kolumn.', $first ?: null);
            }
        }

        return $issues;
    }

    private function iframes(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        foreach ($xpath->query('.//iframe', $root) as $frame) {
            if (trim($frame->getAttribute('title')) === '' && trim($frame->getAttribute('aria-label')) === '') {
                $src = $frame->getAttribute('src');
                $issues[] = $this->issue('iframe_title', 'error', 'Osadzenie „'.$this->short(parse_url($src, PHP_URL_HOST) ?: $src, 50).'” nie ma tytułu.',
                    'Nadaj osadzeniu tytuł opisujący zawartość, np. „Film: Jak działa nasza fundacja”.', $src);
            }
        }

        return $issues;
    }

    /** Kolejne akapity zaczynające się od myślnika lub punktora — to powinna być lista. */
    private function fakeLists(DOMXPath $xpath, DOMElement $root): array
    {
        $issues = [];
        $run = 0;
        $reported = false;
        foreach ($xpath->query('.//p', $root) as $p) {
            $isItem = (bool) preg_match('/^\s*([-–—•*·]|\d+[.)])\s+\S/u', $this->text($p));
            $run = $isItem ? $run + 1 : 0;
            if ($run === 2 && ! $reported) {
                $issues[] = $this->issue('fake_list', 'warning', 'Akapity zaczynające się od myślników lub numerów wyglądają jak lista.',
                    'Użyj listy punktowanej lub numerowanej z edytora — czytnik ekranu ogłosi wtedy liczbę elementów.', $this->short($this->text($p)));
                $reported = true;
            }
        }

        return $issues;
    }

    /** Nazwa dostępna linku: aria-label, tekst, opisy obrazów w środku, title. */
    private function accessibleName(DOMXPath $xpath, DOMElement $a): string
    {
        $aria = trim($a->getAttribute('aria-label'));
        if ($aria !== '') {
            return $aria;
        }
        $text = $this->text($a);
        foreach ($xpath->query('.//img[@alt]', $a) as $img) {
            $text = trim($text.' '.trim($img->getAttribute('alt')));
        }

        return $text !== '' ? $text : trim($a->getAttribute('title'));
    }

    private function text(\DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    }

    private function normalize(string $text): string
    {
        return trim(mb_strtolower(preg_replace('/[\s\p{P}»«›‹→]+/u', ' ', $text) ?? ''));
    }

    private function short(string $text, int $max = 50): string
    {
        return mb_strimwidth($text, 0, $max, '…');
    }

    private function issue(string $rule, string $level, string $message, string $hint, ?string $excerpt = null): array
    {
        return compact('rule', 'level', 'message', 'hint', 'excerpt');
    }
}
