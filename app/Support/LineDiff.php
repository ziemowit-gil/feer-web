<?php

namespace App\Support;

/**
 * Minimalny diff liniowy (LCS) do porównywania wersji treści.
 * Zwraca listę wierszy oznaczonych jako niezmienione / dodane / usunięte.
 */
class LineDiff
{
    /**
     * @return array<int, array{type: 'same'|'add'|'del', text: string}>
     */
    public static function compare(?string $old, ?string $new): array
    {
        $a = self::lines($old);
        $b = self::lines($new);

        [$m, $n] = [count($a), count($b)];

        // Tablica długości najdłuższego wspólnego podciągu.
        $lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $diff = [];
        [$i, $j] = [0, 0];
        while ($i < $m && $j < $n) {
            if ($a[$i] === $b[$j]) {
                $diff[] = ['type' => 'same', 'text' => $a[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $diff[] = ['type' => 'del', 'text' => $a[$i]];
                $i++;
            } else {
                $diff[] = ['type' => 'add', 'text' => $b[$j]];
                $j++;
            }
        }
        while ($i < $m) {
            $diff[] = ['type' => 'del', 'text' => $a[$i++]];
        }
        while ($j < $n) {
            $diff[] = ['type' => 'add', 'text' => $b[$j++]];
        }

        return $diff;
    }

    /** Czy dwie wartości się różnią (po normalizacji). */
    public static function changed(?string $old, ?string $new): bool
    {
        return trim((string) $old) !== trim((string) $new);
    }

    /** @return array<int, string> */
    private static function lines(?string $text): array
    {
        $text = trim((string) $text);

        if ($text === '') {
            return [];
        }

        return preg_split('/\r\n|\r|\n/', $text) ?: [];
    }
}
