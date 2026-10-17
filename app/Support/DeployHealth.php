<?php

namespace App\Support;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Cache;

/**
 * Kontrole kompletności wdrożenia — wspólne dla `artisan system:sprawdz`,
 * deploy.sh i sekcji „Wdrożenie” w panelu (Health Check).
 *
 * Każda kontrola zwraca wiersz:
 *   ['key' => string, 'name' => string, 'status' => 'ok'|'warning'|'error', 'detail' => string]
 */
class DeployHealth
{
    /** Klucz cache, do którego co minutę pisze zadanie harmonogramu (routes/console.php). */
    public const HEARTBEAT_KEY = 'system:scheduler-heartbeat';

    /** Po ilu minutach bez heartbeatu uznajemy, że schedule:run nie działa. */
    public const HEARTBEAT_MAX_AGE_MINUTES = 10;

    /** Dostępne grupy kontroli (opcja --tylko komendy). */
    public const GROUPS = ['manifest', 'migracje', 'blog', 'storage', 'harmonogram'];

    public function __construct(private readonly ?Migrator $migrator = null) {}

    /**
     * @param  list<string>|null  $only  grupy z self::GROUPS; null = wszystkie
     * @return list<array{key: string, name: string, status: string, detail: string}>
     */
    public function run(?array $only = null): array
    {
        $groups = $only ?? self::GROUPS;
        $rows = [];

        if (in_array('manifest', $groups, true)) {
            $rows[] = $this->checkManifest();
            if (is_file(public_path('hot'))) {
                $rows[] = $this->hotFileRow();
            }
        }
        if (in_array('migracje', $groups, true)) {
            $rows[] = $this->checkMigrations(null, $this->mainMigrationPaths(), 'Migracje — baza główna');
            $rows[] = $this->checkMigrations('blog', [database_path('migrations/blog')], 'Migracje — baza bloga');
        }
        if (in_array('blog', $groups, true)) {
            $rows[] = $this->checkBlogDatabase();
        }
        if (in_array('storage', $groups, true)) {
            $rows[] = $this->checkStorageLink();
        }
        if (in_array('harmonogram', $groups, true)) {
            $rows[] = $this->checkScheduler();
        }

        return $rows;
    }

    /** @param  list<array{status: string}>  $rows */
    public static function hasErrors(array $rows): bool
    {
        return collect($rows)->contains(fn ($r) => $r['status'] === 'error');
    }

    // ─── Vite ─────────────────────────────────────────────────────────────

    /**
     * Wejścia `input: [...]` z vite.config.js (parsowanie tekstowe — bez Node).
     *
     * @return list<string>
     */
    public function viteInputs(string $viteConfigPath): array
    {
        if (! is_file($viteConfigPath)) {
            return [];
        }

        $source = (string) file_get_contents($viteConfigPath);
        if (! preg_match('/\binput\s*:\s*\[(.*?)\]/s', $source, $m)) {
            // Pojedyncze wejście: input: 'resources/js/app.js'
            return preg_match('/\binput\s*:\s*([\'"`])([^\'"`]+)\1/', $source, $one) ? [$one[2]] : [];
        }

        preg_match_all('/([\'"`])([^\'"`]+)\1/', $m[1], $all);

        return array_values(array_unique($all[2]));
    }

    /**
     * @param  list<string>  $inputs
     * @return list<string>|null brakujące wejścia; null = brak/niepoprawny manifest
     */
    public function missingManifestEntries(string $manifestPath, array $inputs): ?array
    {
        if (! is_file($manifestPath)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return null;
        }

        return array_values(array_filter($inputs, fn ($input) => ! array_key_exists($input, $manifest)));
    }

    public function checkManifest(?string $manifestPath = null, ?string $viteConfigPath = null): array
    {
        $manifestPath ??= public_path('build/manifest.json');
        $viteConfigPath ??= base_path('vite.config.js');
        $row = ['key' => 'manifest', 'name' => 'Assety (manifest Vite)'];

        $inputs = $this->viteInputs($viteConfigPath);
        if ($inputs === []) {
            return $row + ['status' => 'error', 'detail' => 'nie udało się odczytać listy input z vite.config.js'];
        }

        $missing = $this->missingManifestEntries($manifestPath, $inputs);
        if ($missing === null) {
            return $row + ['status' => 'error', 'detail' => 'brak lub niepoprawny public/build/manifest.json — uruchom npm run build'];
        }
        if ($missing !== []) {
            return $row + ['status' => 'error', 'detail' => 'brak w manifeście: '.implode(', ', $missing).' — uruchom npm run build'];
        }

        return $row + ['status' => 'ok', 'detail' => 'wszystkie wejścia zbudowane ('.count($inputs).')'];
    }

    private function hotFileRow(): array
    {
        $prod = app()->environment('production');

        return [
            'key' => 'hot',
            'name' => 'Plik public/hot',
            'status' => $prod ? 'error' : 'warning',
            'detail' => 'istnieje — strony pobierają assety z serwera deweloperskiego Vite'.($prod ? '; usuń plik na produkcji' : ''),
        ];
    }

    // ─── Migracje ─────────────────────────────────────────────────────────

    /** @return list<string> */
    private function mainMigrationPaths(): array
    {
        return array_values(array_unique(array_merge([database_path('migrations')], $this->migrator()->paths())));
    }

    /**
     * Nazwy migracji z podanych katalogów, których nie ma w tabeli migrations.
     *
     * @param  list<string>  $paths
     * @return list<string>
     */
    public function pendingMigrations(?string $connection, array $paths): array
    {
        $migrator = $this->migrator();

        return $migrator->usingConnection($connection, function () use ($migrator, $paths) {
            $files = array_keys($migrator->getMigrationFiles($paths));
            $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];

            return array_values(array_diff($files, $ran));
        });
    }

    /** @param  list<string>  $paths */
    public function checkMigrations(?string $connection, array $paths, string $name): array
    {
        $row = ['key' => 'migracje-'.($connection ?? 'glowna'), 'name' => $name];

        try {
            $pending = $this->pendingMigrations($connection, $paths);
        } catch (\Throwable $e) {
            return $row + ['status' => 'error', 'detail' => 'nie można sprawdzić: '.$e->getMessage()];
        }

        if ($pending !== []) {
            $list = implode(', ', array_slice($pending, 0, 5)).(count($pending) > 5 ? ' …' : '');

            return $row + ['status' => 'error', 'detail' => count($pending).' oczekujących: '.$list];
        }

        return $row + ['status' => 'ok', 'detail' => 'wszystkie zastosowane'];
    }

    private function migrator(): Migrator
    {
        return $this->migrator ?? app('migrator');
    }

    // ─── Baza bloga ───────────────────────────────────────────────────────

    public function checkBlogDatabase(?string $path = null): array
    {
        $path ??= (string) config('database.connections.blog.database');
        $row = ['key' => 'blog-db', 'name' => 'Plik bazy bloga'];
        $short = str_starts_with($path, base_path().'/') ? substr($path, strlen(base_path()) + 1) : $path;

        if ($path === ':memory:') {
            return $row + ['status' => 'ok', 'detail' => 'baza w pamięci (testy)'];
        }
        if (! is_file($path)) {
            return $row + ['status' => 'error', 'detail' => "brak pliku {$short}"];
        }
        if (! is_writable($path) || ! is_writable(dirname($path))) {
            return $row + ['status' => 'error', 'detail' => "{$short} lub jego katalog nie jest zapisywalny"];
        }
        if ($path === BlogDatabasePath::legacyPath()) {
            return $row + ['status' => 'warning', 'detail' => "{$short} — stara lokalizacja śledzona w gicie; uruchom artisan blog:przenies-baze"];
        }

        return $row + ['status' => 'ok', 'detail' => $short];
    }

    // ─── storage:link ─────────────────────────────────────────────────────

    public function checkStorageLink(): array
    {
        $link = public_path('storage');
        $row = ['key' => 'storage-link', 'name' => 'Link public/storage'];

        if (! file_exists($link)) {
            return $row + ['status' => 'error', 'detail' => 'brak — uruchom artisan storage:link'];
        }

        $real = realpath($link);
        $expected = realpath(storage_path('app/public'));
        if ($real === false || $real !== $expected) {
            return $row + ['status' => 'error', 'detail' => 'wskazuje na '.($real ?: '?').' zamiast storage/app/public'];
        }

        return $row + ['status' => 'ok', 'detail' => is_link($link) ? 'dowiązanie poprawne' : 'katalog poprawny'];
    }

    // ─── Harmonogram ──────────────────────────────────────────────────────

    /** Plik ze znacznikiem — przetrwa `cache:clear` w deploy.sh (inaczej każdy deploy dawał fałszywe „brak sygnału”). */
    public static function heartbeatFile(): string
    {
        return storage_path('app/scheduler-heartbeat');
    }

    public static function recordHeartbeat(): void
    {
        $now = now()->getTimestamp();
        Cache::put(self::HEARTBEAT_KEY, $now, now()->addDay());
        @file_put_contents(self::heartbeatFile(), (string) $now);
    }

    public static function lastHeartbeat(): ?int
    {
        $last = Cache::get(self::HEARTBEAT_KEY);
        if (! is_numeric($last) && is_file(self::heartbeatFile())) {
            $last = trim((string) @file_get_contents(self::heartbeatFile()));
        }

        return is_numeric($last) ? (int) $last : null;
    }

    public function checkScheduler(): array
    {
        $row = ['key' => 'harmonogram', 'name' => 'Harmonogram (schedule:run)'];

        try {
            $last = self::lastHeartbeat();
        } catch (\Throwable $e) {
            return $row + ['status' => 'warning', 'detail' => 'nie można odczytać cache: '.$e->getMessage()];
        }

        if ($last === null) {
            // Alias php84 w cronie nie działa — podpowiadamy pełną ścieżkę i skrypt, który wpis dodaje.
            return $row + ['status' => 'warning', 'detail' => 'brak sygnału — uruchom scripts/cron.sh (wpis: * * * * * cd '.base_path().' && '.\App\Support\CronTab::phpBinary().' artisan schedule:run)'];
        }

        $minutes = (int) floor((now()->getTimestamp() - (int) $last) / 60);
        if ($minutes > self::HEARTBEAT_MAX_AGE_MINUTES) {
            return $row + ['status' => 'warning', 'detail' => "ostatnie uruchomienie {$minutes} min temu — sprawdź crona"];
        }

        return $row + ['status' => 'ok', 'detail' => $minutes < 1 ? 'działa (przed chwilą)' : "działa ({$minutes} min temu)"];
    }
}
