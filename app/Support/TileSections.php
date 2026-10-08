<?php

namespace App\Support;

/**
 * Grupowanie kafelków w sekcje: wiersz z kluczem `heading` otwiera nową sekcję (nagłówek + siatka kolejnych kafelków).
 */
class TileSections
{
    /** @return array<int, array{heading: ?string, tiles: array}> grupy z co najmniej jednym kafelkiem */
    public static function groups(?array $rows): array
    {
        $groups = [['heading' => null, 'tiles' => []]];

        foreach ((array) $rows as $t) {
            if (filled($t['heading'] ?? null)) {
                $groups[] = ['heading' => trim($t['heading']), 'tiles' => []];
            } elseif (filled($t['label'] ?? null) && filled($t['url'] ?? null)) {
                $groups[array_key_last($groups)]['tiles'][] = $t;
            }
        }

        return array_values(array_filter($groups, fn ($g) => $g['tiles'] !== []));
    }
}
