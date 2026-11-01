<?php

namespace App\Support;

/**
 * Kolory stylu panelu „Metro” (User::ADMIN_SCHEMES['metro'], style w admin/partials/theme-metro): jasne tła strony i menu
 * oraz paleta kafli.
 *
 * Kafel to cztery kolory: tło, tekst oraz tło po najechaniu i po wciśnięciu. Kontrast tekstu do tła jest ≥ 4,5:1
 * w każdym stanie, także na powierzchniach szarych (pilnuje tego AdminMetroStyleTest). Kolor marki witryny przechodzi
 * przez Color::button(), więc nawet nietypowy kolor marki daje czytelne kafle. Poza marką tylko kolory z brandbooka FEER:
 * jasne odcienie niebieskiego i pomarańczu, ink jako tekst i szarości — pomarańcz z ciemnym tekstem (z białym
 * miałby ok. 2,5:1).
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
            // Jasne odcienie z ciemnym tekstem: błękit marki, pomarańcz marki, chłodna szarość.
            'sky' => self::tint($color, 0.82),
            'sand' => self::tint(self::AMBER, 0.72),
            'mist' => ['bg' => '#dfe3e8', 'fg' => self::INK, 'hover' => '#d3d8de', 'active' => '#c6ccd3'],
            // Pilne sprawy: jasny pomarańcz z ciemnym tekstem.
            'amber' => self::solid(self::AMBER, self::INK),
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

    /**
     * Jasne tło z domieszki koloru do bieli ($white = udział bieli) i ciemny tekst; po najechaniu i wciśnięciu nieco
     * ciemniejsze, ale wciąż jasne (kontrast ciemnego tekstu rośnie wraz z jasnością tła).
     *
     * @return array{bg: string, fg: string, hover: string, active: string}
     */
    private static function tint(string $color, float $white): array
    {
        return [
            'bg' => self::mix($color, self::WHITE, $white),
            'fg' => self::INK,
            'hover' => self::mix($color, self::WHITE, $white - 0.08),
            'active' => self::mix($color, self::WHITE, $white - 0.16),
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
