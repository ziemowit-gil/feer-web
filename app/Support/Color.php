<?php

namespace App\Support;

/**
 * Small colour utilities for user-chosen CTA colours. Keeps the WCAG contrast
 * maths in one place so buttons stay readable whatever colour an editor picks.
 */
class Color
{
    /**
     * True for a well-formed "#rrggbb" string.
     */
    public static function isValid(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1;
    }

    /**
     * The text colour (white or near-black) that reads best on the given
     * background — whichever gives the higher contrast ratio (WCAG 1.4.3).
     */
    public static function readableTextOn(string $background): string
    {
        $dark = '#111827'; // Tailwind ink / gray-900

        return self::contrast($background, '#ffffff') >= self::contrast($background, $dark)
            ? '#ffffff'
            : $dark;
    }

    /**
     * A WCAG AA-compliant button palette for a chosen background colour.
     * Returns [bg, text, hover]. The text is black or white — whichever reads
     * best — and if neither reaches 4.5:1 on the chosen colour (a narrow band
     * of vivid mid-tones), the background is darkened just enough to make white
     * text pass, preserving the hue while guaranteeing contrast.
     */
    public static function button(string $background): array
    {
        $white = '#ffffff';
        $ink = '#111827';
        $threshold = 4.5;

        $contrastWhite = self::contrast($background, $white);
        $contrastInk = self::contrast($background, $ink);

        if ($contrastWhite >= $threshold || $contrastInk >= $threshold) {
            $bg = $background;
            $text = $contrastWhite >= $contrastInk ? $white : $ink;
        } else {
            $bg = $background;

            for ($step = 1; $step <= 20; $step++) {
                $bg = self::darken($background, 0.05 * $step);

                if (self::contrast($bg, $white) >= $threshold) {
                    break;
                }
            }

            $text = $white;
        }

        return ['bg' => $bg, 'text' => $text, 'hover' => self::darken($bg, 0.15)];
    }

    /**
     * A slightly darker shade of the colour, for button hover states.
     */
    public static function darken(string $hex, float $amount = 0.15): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }

        [$r, $g, $b] = array_map(fn ($part) => hexdec($part), str_split($hex, 2));
        $mix = fn ($channel) => (int) round($channel * (1 - $amount));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    public static function contrast(string $hexA, string $hexB): float
    {
        $lighter = max(self::luminance($hexA), self::luminance($hexB));
        $darker = min(self::luminance($hexA), self::luminance($hexB));

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return 0;
        }

        [$r, $g, $b] = array_map(function ($part) {
            $channel = hexdec($part) / 255;

            return $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
