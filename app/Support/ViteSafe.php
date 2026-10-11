<?php

namespace App\Support;

/**
 * Sprawdza, czy wpisy Vite są w zbudowanym manifeście (public/build/manifest.json).
 * Po wdrożeniu kodu bez przebudowy assetów (`npm run build`) brakujący wpis kończy się
 * wyjątkiem i błędem 500 — widok może zamiast tego załadować tylko dostępne pliki
 * i pokazać komunikat. W trybie deweloperskim (plik `public/hot`) niczego nie filtruje.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ViteSafe
{
    /** @return list<string> wpisy, których brakuje w manifeście */
    public static function missing(array $entries): array
    {
        if (is_file(public_path('hot'))) {
            return [];
        }

        static $manifest = null;
        if ($manifest === null) {
            $path = public_path('build/manifest.json');
            $manifest = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        }

        return array_values(array_filter($entries, fn ($e) => ! array_key_exists($e, $manifest)));
    }
}
