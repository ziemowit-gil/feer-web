<?php

namespace App\Support;

use App\Models\Faq;
use App\Models\SiteSetting;

/**
 * Sekcja FAQ do wstawienia na dowolną podstronę: pytania z modułu FAQ (Treści → FAQ) w nowym układzie
 * (nagłówek z opisem po lewej, akordeon z numerowanymi pytaniami po prawej, dane FAQPage dla wyszukiwarek).
 *
 * Wstawka: [faq] — wszystkie opublikowane pytania; [faq: Szkolenia, Edukacja] — wybrane kategorie;
 * opcje po średnikach: [faq: Szkolenia; tytul=Pytania o szkolenia; wstep=Krótki opis; limit=5; poziom=3].
 */
class FaqSection
{
    /** Licznik sekcji na stronie — unikalne id nagłówków, gdy sekcji jest kilka. */
    private static int $count = 0;

    /** @return array{categories: array<int, string>, title: string, intro: string, limit: int|null, level: int} */
    public static function parse(string $args): array
    {
        $opts = ['categories' => [], 'title' => '', 'intro' => '', 'limit' => null, 'level' => 2];
        foreach (explode(';', html_entity_decode(strip_tags($args), ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $i => $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^(tytul|tytuł|wstep|wstęp|limit|poziom)\s*=\s*(.*)$/iu', $part, $m)) {
                $key = strtr(mb_strtolower($m[1]), ['ł' => 'l', 'ę' => 'e']);
                $val = trim($m[2]);
                match ($key) {
                    'tytul' => $opts['title'] = mb_substr($val, 0, 120),
                    'wstep' => $opts['intro'] = mb_substr($val, 0, 400),
                    'limit' => $opts['limit'] = max(1, min(50, (int) $val)) ?: null,
                    'poziom' => $opts['level'] = in_array((int) $val, [2, 3, 4], true) ? (int) $val : 2,
                };
            } elseif ($i === 0) {
                $opts['categories'] = array_values(array_filter(array_map('trim', explode(',', $part)), fn ($c) => $c !== ''));
            }
        }

        return $opts;
    }

    /**
     * Atrybuty bloku „FAQ z modułu” (edytor blokowy) — te same pola co opcje wstawki.
     *
     * @param  array<string, mixed>  $a
     * @return array{categories: array<int, string>, title: string, intro: string, limit: int|null, level: int}
     */
    public static function normalize(array $a): array
    {
        $clean = fn ($v, int $max) => mb_substr(trim(str_replace([';', ']', '[', "\r", "\n"], ' ', (string) $v)), 0, $max);
        $cats = array_values(array_unique(array_filter(array_map(fn ($c) => $clean(str_replace(',', ' ', (string) $c), 120), (array) ($a['categories'] ?? [])), fn ($c) => $c !== '')));
        $limit = (int) ($a['limit'] ?? 0);

        return [
            'categories' => array_slice($cats, 0, 20),
            'title' => $clean($a['title'] ?? '', 120),
            'intro' => $clean($a['intro'] ?? '', 400),
            'limit' => $limit > 0 ? min(50, $limit) : null,
            'level' => in_array((int) ($a['level'] ?? 2), [2, 3, 4], true) ? (int) ($a['level'] ?? 2) : 2,
        ];
    }

    /** Wstawka [faq…] z atrybutów bloku (odwrotność parse()). */
    public static function shortcode(array $a): string
    {
        $a = self::normalize($a);
        $opts = array_filter([
            $a['title'] !== '' ? 'tytul='.$a['title'] : null,
            $a['intro'] !== '' ? 'wstep='.$a['intro'] : null,
            $a['limit'] !== null ? 'limit='.$a['limit'] : null,
            $a['level'] !== 2 ? 'poziom='.$a['level'] : null,
        ]);
        $cats = implode(', ', $a['categories']);
        if ($cats === '' && $opts === []) {
            return '[faq]';
        }

        // Bez kategorii pierwszy człon zostaje pusty („[faq: ; tytul=…]”), bo parse() czyta kategorie tylko z pierwszego.
        return '[faq: '.$cats.($opts !== [] ? '; '.implode('; ', $opts) : '').']';
    }

    /** Kategorie istniejących pytań (do wyboru w edytorze blokowym). @return array<int, string> */
    public static function categoryOptions(): array
    {
        return Faq::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->all();
    }

    /** @param array{categories: array<int, string>, title: string, intro: string, limit: int|null, level: int} $opts */
    public static function render(array $opts): string
    {
        if (! SiteSetting::current()->isModuleEnabled('faq')) {
            return '';
        }

        $query = Faq::published();
        if ($opts['categories'] !== []) {
            $query->whereIn('category', $opts['categories']);
        }
        $items = $query->get();
        if ($opts['limit'] !== null) {
            $items = $items->take($opts['limit']);
        }
        if ($items->isEmpty()) {
            return '';
        }

        return view('partials.faq-section', [
            'items' => $items,
            'title' => $opts['title'] !== '' ? $opts['title'] : 'Najczęstsze pytania',
            'intro' => $opts['intro'],
            'level' => $opts['level'],
            'uid' => 'faq-sec-'.(++self::$count),
        ])->render();
    }
}
