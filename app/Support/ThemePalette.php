<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Paleta motywu (kolor główny + kolory 2–4 z Ustawienia → Kolory) dla widoków i konstruktorów treści,
 * które potrzebują kolorów „po nazwie" lub listy próbek. Domyślnie Brand book FEER 2024.
 */
class ThemePalette
{
    private const DEFAULTS = ['#1e6dff', '#ea8f00', '#1d1d1a', '#cbd5e7'];

    /** [kolor główny, kolor 2, kolor 3, kolor 4] — zawsze poprawne wartości hex (małe litery). */
    public static function colors(): array
    {
        try {
            $s = SiteSetting::current();
            $raw = [$s->brand_color, $s->brand_color_2, $s->brand_color_3, $s->brand_color_4];
        } catch (\Throwable) {
            $raw = []; // np. strona błędu bez dostępu do bazy
        }

        return array_map(fn ($v, $d) => is_string($v) && Color::isValid($v) ? strtolower($v) : $d, $raw + [null, null, null, null], self::DEFAULTS);
    }

    /** Kolejność dawnej palety kafelków: kolor główny, grafit (3), kolor 2, jasny (4). */
    public static function tiles(): array
    {
        [$b1, $b2, $b3, $b4] = self::colors();

        return [$b1, $b3, $b2, $b4];
    }

    /** [hex => nazwa] do wyboru koloru w formularzach. */
    public static function swatches(): array
    {
        [$b1, $b2, $b3, $b4] = self::colors();

        return [$b1 => 'Kolor główny', $b3 => 'Kolor 3 (ciemny)', $b2 => 'Kolor 2', $b4 => 'Kolor 4 (jasny)'];
    }

    /** Kolory nazwane (blue/dark/orange…) mapowane na paletę motywu; zielony i czerwony zostają statusowe. */
    public static function named(): array
    {
        [$b1, $b2, $b3] = self::colors();

        return ['blue' => $b1, 'dark' => $b3, 'green' => '#166534', 'purple' => $b1, 'orange' => $b2, 'red' => '#b91c1c'];
    }

    /** Czytelny kolor tekstu (ciemny/biały) na danym tle. */
    public static function textOn(string $bg): string
    {
        return Color::readableTextOn($bg);
    }

    /** Czy wyłączono poprawianie kolorów do WCAG (Ustawienia → Kolory). */
    public static function skipWcag(): bool
    {
        try {
            return (bool) SiteSetting::current()->brand_skip_contrast;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * {bg, text, hover} dla kafla/przycisku w danym kolorze: kolor główny motywu zostaje dokładnie taki, jaki ustawiono
     * (biały tekst), a przy włączonej opcji „Nie poprawiaj do WCAG" żaden kolor nie jest korygowany.
     */
    public static function button(string $base): array
    {
        $base = strtolower($base);
        if (self::skipWcag() || $base === self::colors()[0]) {
            $text = Color::contrast($base, '#ffffff') >= 4.4 || self::skipWcag() && Color::contrast($base, '#ffffff') >= Color::contrast($base, '#1d1d1a')
                ? '#ffffff' : '#1d1d1a';

            return ['bg' => $base, 'text' => $text, 'hover' => $base];
        }

        return Color::button($base);
    }

    /**
     * Kolor tekstu/linków w kolorze marki czytelny na bieli i na jasnym tle marki (≥ 4,6:1 — zapas ponad 4,5:1).
     * Przy wyłączonym poprawianiu do WCAG zwraca kolor bez zmian.
     */
    public static function textColor(?string $hex = null, string $light = '#e8f0ff'): string
    {
        $hex = strtolower($hex ?? self::colors()[0]);
        if (self::skipWcag()) {
            return $hex;
        }
        for ($i = 0; $i < 20; $i++) {
            if (Color::contrast($hex, '#ffffff') >= 4.6 && Color::contrast($hex, $light) >= 4.6) {
                return $hex;
            }
            $hex = Color::darken($hex, 0.05);
        }

        return $hex;
    }
}
