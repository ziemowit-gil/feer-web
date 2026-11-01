<?php

namespace App\Support;

/**
 * Paleta kafli stylu panelu „Metro” (User::ADMIN_SCHEMES['metro'], style w admin/partials/theme-metro).
 *
 * Każdy ton to cztery kolory: tło, tekst oraz tło po najechaniu i po wciśnięciu. Kontrast tekstu do tła jest
 * ≥ 4,5:1 w każdym stanie (pilnuje tego AdminMetroStyleTest). Kolor marki witryny przechodzi przez Color::button(),
 * więc nawet nietypowy kolor marki daje czytelne kafle. Poza marką tylko kolory z brandbooka FEER: ink, pomarańcz
 * i szarości — pomarańcz z ciemnym tekstem (z białym miałby ok. 2,5:1).
 */
final class AdminMetro
{
    public const INK = '#1d1d1a';

    public const WHITE = '#ffffff';

    public const AMBER = '#ea8f00';

    public const SLATE = '#4b5563';

    /** Tło komunikatów o błędzie (białe litery ≈ 6,5:1). */
    public const DANGER = '#b3261e';

    /**
     * @param  string  $brandDark  ciemny odcień koloru marki (SiteSetting::brandPalette()['dark'])
     * @return array<string, array{bg: string, fg: string, hover: string, active: string}>
     */
    public static function tones(string $brandDark): array
    {
        $brand = Color::button(Color::isValid($brandDark) ? $brandDark : '#1752bf');
        $deep = Color::button(Color::darken($brand['bg'], 0.30));

        return [
            'brand' => self::tone($brand['bg'], $brand['text']),
            'deep' => self::tone($deep['bg'], $deep['text']),
            'ink' => self::tone(self::INK, self::WHITE, lighten: true),
            'slate' => self::tone(self::SLATE, self::WHITE),
            'amber' => self::tone(self::AMBER, self::INK),
            'paper' => ['bg' => '#ffffff', 'fg' => self::INK, 'hover' => '#f4f4f4', 'active' => '#e4e4e4'],
        ];
    }

    /**
     * Stany tonu: przy jasnym tekście tło ciemnieje, przy ciemnym tekście (i prawie czarnym tle) jaśnieje —
     * kontrast względem podstawowego stanu tylko rośnie albo zostaje bardzo wysoki.
     *
     * @return array{bg: string, fg: string, hover: string, active: string}
     */
    private static function tone(string $bg, string $fg, bool $lighten = false): array
    {
        $toWhite = $lighten || $fg !== self::WHITE;

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
