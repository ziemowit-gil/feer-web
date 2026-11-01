<?php

namespace App\Support;

/**
 * Kolory stylu panelu „Metro” (User::ADMIN_SCHEMES['metro'], style w admin/partials/theme-metro): jasne tła strony i menu
 * oraz paleta kafli.
 *
 * Kafel to cztery kolory: tło, tekst oraz tło po najechaniu i po wciśnięciu. Kontrast tekstu do tła jest ≥ 4,5:1
 * w każdym stanie, także na powierzchniach szarych (pilnuje tego AdminMetroStyleTest). Kolor marki witryny przechodzi
 * przez Color::button(), więc nawet nietypowy kolor marki daje czytelne kafle. Poza marką tylko kolory z brandbooka FEER:
 * pomarańcz z ciemnym tekstem (z białym miałby ok. 2,5:1), ink jako tekst i biel. Pulpit używa trzech tonów: brand
 * („Utwórz”), amber („Na dziś”) i paper (komunikat „Wszystko załatwione”).
 */
final class AdminMetro
{
    public const INK = '#1d1d1a';

    public const WHITE = '#ffffff';

    public const AMBER = '#ea8f00';

    /** Tekst pomocniczy (kontrast ≥ 4,5:1 na każdej powierzchni poniżej i na bieli). */
    public const MUTED = '#4b5563';

    /** Tło komunikatów o błędzie (białe litery ≈ 6,5:1). */
    public const DANGER = '#b3261e';

    /** Powierzchnie panelu: tło strony, pasek boczny, najechany wiersz paska, najechany wiersz listy, nagłówki tabel. */
    public const SURFACES = [
        'page' => '#f3f3f3',
        'side' => '#ebebeb',
        'side_hover' => '#dcdcdc',
        'row_hover' => '#f7f7f7',
        'head' => '#ececec',
    ];

    /**
     * @param  string  $brandColor  kolor marki witryny (SiteSetting::brandPalette()['color'])
     * @return array<string, array{bg: string, fg: string, hover: string, active: string}>
     */
    public static function tones(string $brandColor): array
    {
        $color = Color::isValid($brandColor) ? $brandColor : '#1e6dff';
        $brand = Color::button($color);

        return [
            // Żywy niebieski marki z białym tekstem (akcent: aktywna grupa menu, kafle „Utwórz”).
            'brand' => self::solid($brand['bg'], $brand['text']),
            // Pilne sprawy („Na dziś”): jasny pomarańcz z ciemnym tekstem.
            'amber' => self::solid(self::AMBER, self::INK),
            // Biały kafel-komunikat („Wszystko załatwione”).
            'paper' => ['bg' => '#ffffff', 'fg' => self::INK, 'hover' => '#f4f4f4', 'active' => '#e4e4e4'],
        ];
    }

    /**
     * Pełne tło: przy jasnym tekście ciemnieje po najechaniu i wciśnięciu, przy ciemnym tekście jaśnieje —
     * kontrast względem podstawowego stanu tylko rośnie.
     *
     * @return array{bg: string, fg: string, hover: string, active: string}
     */
    private static function solid(string $bg, string $fg): array
    {
        $toWhite = $fg !== self::WHITE;

        return [
            'bg' => $bg,
            'fg' => $fg,
            'hover' => $toWhite ? self::mix($bg, self::WHITE, 0.12) : Color::darken($bg, 0.15),
            'active' => $toWhite ? self::mix($bg, self::WHITE, 0.24) : Color::darken($bg, 0.30),
        ];
    }

    /** Miesza dwa kolory #rrggbb: $amount to udział koloru $with (0–1). */
    private static function mix(string $hex, string $with, float $amount): string
    {
        [$r1, $g1, $b1] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        [$r2, $g2, $b2] = array_map('hexdec', str_split(ltrim($with, '#'), 2));
        $channel = fn (int $a, int $b): int => (int) round($a * (1 - $amount) + $b * $amount);

        return sprintf('#%02x%02x%02x', $channel($r1, $r2), $channel($g1, $g2), $channel($b1, $b2));
    }
}
