<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Przenośność treści między instalacjami (np. przy przeprowadzce na inny
 * hosting). Eksport pakuje treść z bazy oraz pliki mediów do jednego ZIP-a;
 * import wgrywa je z powrotem metodą „upsert po ID" — nigdy nie kasuje
 * istniejących wierszy, więc wgranie na działający system nie wywala treści.
 *
 * Świadomie POMIJAMY: tabele frameworka (cache, kolejki, sesje, migracje),
 * konta i uprawnienia (users, user_groups) oraz zgłoszenia z formularzy
 * (dane osobowe: zapisy, spotkania, bariery). Blog działa na osobnym
 * połączeniu i nie wchodzi w zakres tej wersji.
 */
class ContentPortability
{
    /** Format pliku — pozwala w przyszłości wykryć niezgodną wersję paczki. */
    public const FORMAT_VERSION = 1;

    /** Tabele pomijane w eksporcie (framework, auth, dane osobowe). */
    private const EXCLUDED_TABLES = [
        'migrations', 'sqlite_sequence',
        'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs',
        'sessions', 'password_reset_tokens',
        'users', 'user_groups',
        'material_subscribers', 'meeting_signups', 'accessibility_reports',
    ];

    /**
     * Zbuduj paczkę ZIP z treścią i mediami. Zwraca ścieżkę do pliku.
     */
    public function export(string $destination): string
    {
        $payload = [
            'format' => self::FORMAT_VERSION,
            'app' => config('app.name'),
            'tables' => [],
        ];

        foreach ($this->contentTables() as $table) {
            $payload['tables'][$table] = DB::table($table)->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        File::ensureDirectoryExists(dirname($destination));

        $zip = new ZipArchive;
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Nie można utworzyć pliku ZIP: {$destination}");
        }

        $zip->addFromString('content.json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Pliki mediów (dysk „public" = storage/app/public) — dzięki nim po
        // imporcie wracają wszystkie zdjęcia i logotypy powiązane w tabeli media.
        $publicRoot = Storage::disk('public')->path('');
        if (is_dir($publicRoot)) {
            foreach (File::allFiles($publicRoot) as $file) {
                $relative = ltrim(str_replace($publicRoot, '', $file->getPathname()), '/\\');
                $zip->addFile($file->getPathname(), 'media/'.$relative);
            }
        }

        $zip->close();

        return $destination;
    }

    /**
     * Wgraj paczkę ZIP. Zwraca podsumowanie (liczba wierszy na tabelę).
     * Upsert po kluczu głównym — istniejące, nieobjęte paczką wiersze zostają.
     *
     * @return array<string,int>
     */
    public function import(string $zipPath): array
    {
        if (! is_file($zipPath)) {
            throw new \RuntimeException("Plik nie istnieje: {$zipPath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Nie można otworzyć paczki ZIP.');
        }

        $json = $zip->getFromName('content.json');
        if ($json === false) {
            $zip->close();
            throw new \RuntimeException('Paczka nie zawiera pliku content.json.');
        }

        $payload = json_decode($json, true);
        if (! is_array($payload) || ($payload['format'] ?? null) !== self::FORMAT_VERSION) {
            $zip->close();
            throw new \RuntimeException('Nieprawidłowy lub niezgodny format paczki.');
        }

        $summary = [];
        $available = $this->contentTables();

        Schema::withoutForeignKeyConstraints(function () use ($payload, $available, &$summary) {
            DB::transaction(function () use ($payload, $available, &$summary) {
                foreach ($payload['tables'] as $table => $rows) {
                    // Import tylko do tabel, które istnieją i są treścią (nie auth itp.).
                    if (! in_array($table, $available, true)) {
                        continue;
                    }

                    foreach ($rows as $row) {
                        $match = array_key_exists('id', $row) ? ['id' => $row['id']] : $row;
                        DB::table($table)->updateOrInsert($match, $row);
                    }

                    $summary[$table] = count($rows);
                }
            });
        });

        // Odtwórz pliki mediów (nadpisuje pliki o tej samej ścieżce, nie kasuje innych).
        $publicRoot = rtrim(Storage::disk('public')->path(''), '/\\');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! str_starts_with($name, 'media/') || str_ends_with($name, '/')) {
                continue;
            }
            $relative = substr($name, strlen('media/'));
            $target = $publicRoot.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));
            file_put_contents($target, $zip->getFromIndex($i));
        }

        $zip->close();

        return $summary;
    }

    /**
     * Tabele treści = wszystkie istniejące tabele poza listą wykluczeń.
     * „Wszystko poza wykluczeniami" jest odporne na przyszłe tabele treści.
     *
     * @return array<int,string>
     */
    public function contentTables(): array
    {
        return collect(Schema::getTableListing())
            ->map(fn ($name) => str_contains($name, '.') ? explode('.', $name)[1] : $name)
            ->reject(fn ($name) => in_array($name, self::EXCLUDED_TABLES, true))
            ->values()
            ->all();
    }
}
