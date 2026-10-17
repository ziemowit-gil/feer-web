<?php

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;
use RuntimeException;
use Throwable;

/**
 * Przenosi bazę główną z SQLite do MySQL/MariaDB (panel: System → Baza MySQL, konsola: db:konwertuj-mysql).
 *
 * Kroki: sprawdzenie połączenia → schemat w bazie docelowej z migracji (te same co w SQLite) → wyczyszczenie
 * wierszy dodanych przez migracje (np. domyślne strefy banerów) → kopia danych tabela po tabeli w paczkach
 * (wspólne kolumny, wyłączone klucze obce) → tabela migrations 1:1 → porównanie liczby wierszy.
 * Przełączenie (.env) jest osobnym krokiem z kopią pliku .env i możliwością powrotu do SQLite.
 * Baza bloga („blog”) zostaje w swoim pliku SQLite.
 */
class SqliteToMysqlConverter
{
    public const TARGET = 'konwersja_mysql';

    public const CHUNK = 500;

    /**
     * Tabele kopiowane bez danych. Cache: magazyn bazodanowy Laravela na SQLite koduje w base64 wartości
     * z bajtem NUL, a na MySQL nie dekoduje — skopiowane wpisy dawały „unserialize(): Error at offset 0”
     * (błąd 500 na stronie). Cache odbuduje się sam.
     */
    public const SKIP_DATA = ['cache', 'cache_locks'];

    /** Klucze .env zmieniane przy przełączeniu na MySQL. */
    public const ENV_KEYS = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

    private string $source;

    /**
     * @param  string|null  $source  połączenie źródłowe (domyślnie bieżące — musi być SQLite)
     * @param  array<string, mixed>|null  $targetConfig  pełna konfiguracja połączenia docelowego (testy: inny plik SQLite)
     */
    public function __construct(?string $source = null, ?array $targetConfig = null)
    {
        $this->source = $source ?? (string) config('database.default');
        if ($targetConfig !== null) {
            $this->useTarget($targetConfig);
        }
    }

    /**
     * Konfiguracja docelowego MySQL z danych formularza / opcji polecenia (reszta jak w połączeniu „mysql”).
     *
     * @param  array{host: string, port: int|string, database: string, username: string, password: ?string}  $c
     * @return array<string, mixed>
     */
    public static function mysqlConfig(array $c): array
    {
        return array_merge(config('database.connections.mysql'), [
            'url' => null,
            'host' => $c['host'],
            'port' => (string) $c['port'],
            'database' => $c['database'],
            'username' => $c['username'],
            'password' => (string) ($c['password'] ?? ''),
            'unix_socket' => '',
            // sql_mode ustawiamy sami przy kopiowaniu (ścisły, z NO_AUTO_VALUE_ON_ZERO) — patrz withoutForeignKeys().
            'strict' => false,
            'options' => (config('database.connections.mysql.options') ?? []) + (extension_loaded('pdo_mysql') ? [\PDO::ATTR_TIMEOUT => 5] : []),
        ]);
    }

    /** @param  array<string, mixed>  $config */
    public function useTarget(array $config): static
    {
        Config::set('database.connections.'.self::TARGET, $config);
        DB::purge(self::TARGET);

        return $this;
    }

    private function src(): Connection
    {
        return DB::connection($this->source);
    }

    private function dst(): Connection
    {
        return DB::connection(self::TARGET);
    }

    /**
     * Sprawdza źródło i połączenie docelowe.
     *
     * @return array{source_driver: string, source_tables: int, source_rows: int, target_driver: string, target_version: ?string, target_tables: int, target_empty: bool}
     */
    public function check(): array
    {
        if ($this->src()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Bieżąca baza nie jest SQLite (sterownik: '.$this->src()->getDriverName().') — nie ma czego konwertować.');
        }

        try {
            $this->dst()->getPdo();
        } catch (Throwable $e) {
            throw new RuntimeException('Nie udało się połączyć z bazą docelową: '.self::friendly($e));
        }

        $tables = $this->sourceTables();
        $targetTables = $this->targetTables();

        return [
            'source_driver' => 'sqlite',
            'source_tables' => count($tables),
            'source_rows' => array_sum(array_map(fn ($t) => $this->src()->table($t)->count(), $tables)),
            'target_driver' => $this->dst()->getDriverName(),
            'target_version' => $this->isMysql() ? (string) $this->dst()->selectOne('select version() as v')->v : null,
            'target_tables' => count($targetTables),
            'target_empty' => $targetTables === [],
        ];
    }

    /**
     * Konwersja. $fresh = baza docelowa może zawierać tabele (np. powtórna konwersja tuż przed przełączeniem) — zostaną usunięte.
     *
     * @param  callable(string): void|null  $progress
     * @return array{ok: bool, tables: list<array{table: string, source: int, target: int, ok: bool, note: ?string}>, skipped: list<string>, seconds: float, finished_at: string}
     */
    public function convert(bool $fresh = false, ?callable $progress = null): array
    {
        $started = microtime(true);
        $say = $progress ?? fn (string $m) => null;
        $check = $this->check();

        if (! $check['target_empty'] && ! $fresh) {
            throw new RuntimeException('Baza docelowa zawiera już tabele ('.$check['target_tables'].'). Zaznacz „Zastąp zawartość bazy docelowej”, aby utworzyć ją od nowa.');
        }

        // 1. Schemat z migracji. once() pamięta np. Schema::hasColumn() z bazy źródłowej (hook Page::saving) —
        // starsze migracje zapisujące modele w nowej bazie wpisałyby kolumny, których tam jeszcze nie ma.
        $say('Tworzenie tabel w bazie docelowej (migracje)…');
        Once::flush();
        if ($this->isMysql()) {
            // site_settings ma ponad 150 kolumn string — jako VARCHAR(255) przekroczyłyby limit wiersza MySQL.
            $grammar = new WideTableMySqlGrammar($this->dst());
            $grammar->wideTables = $this->wideTables();
            $this->dst()->setSchemaGrammar($grammar);
            if ($grammar->wideTables) {
                $say('Szerokie tabele (kolumny tekstowe jako TEXT): '.implode(', ', $grammar->wideTables));
            }
        }
        // Migracje z danymi zapisują modele (np. strony), a obserwatory dopisują wersje i dziennik z id zalogowanej
        // osoby — w nowej bazie tabela users jest jeszcze pusta. Na czas migracji: bez zalogowanego użytkownika
        // i bez kontroli kluczy obcych (te wiersze i tak zastąpią dane ze źródła).
        // Samo forgetUser() nie wystarcza — strażnik sesji przy następnym auth()->id() wczytałby osobę z sesji.
        // Dlatego na czas migracji przełączamy się na pusty strażnik (zawsze „brak użytkownika”) i przywracamy poprzedni.
        $previousGuard = (string) config('auth.defaults.guard');
        Auth::viaRequest('konwersja-bez-uzytkownika', fn () => null);
        Config::set('auth.guards.konwersja_bez_uzytkownika', ['driver' => 'konwersja-bez-uzytkownika']);
        Auth::shouldUse('konwersja_bez_uzytkownika');
        try {
            // migrate:fresh przy czyszczeniu (dropAllTables) z powrotem włącza klucze obce — czyścimy sami.
            if ($fresh) {
                Schema::connection(self::TARGET)->dropAllTables();
            }
            $this->foreignKeys(false);
            $exit = Artisan::call('migrate', ['--database' => self::TARGET, '--force' => true]);
        } finally {
            $this->foreignKeys(true);
            Auth::shouldUse($previousGuard);
            Once::flush();
        }
        if ($exit !== 0) {
            throw new RuntimeException('Migracje w bazie docelowej nie powiodły się: '.trim(Artisan::output()));
        }

        $sourceTables = $this->sourceTables();
        $targetTables = $this->targetTables();
        $report = [];
        $skipped = [];

        $this->withoutForeignKeys(function () use ($sourceTables, $targetTables, &$report, &$skipped, $say) {
            // 2. Wiersze dodane przez migracje (domyślne dane) — zastąpi je kopia ze źródła.
            foreach ($targetTables as $t) {
                $this->dst()->table($t)->delete();
            }

            // 3. Dane.
            foreach ($sourceTables as $table) {
                if (! in_array($table, $targetTables, true)) {
                    $skipped[] = $table;

                    continue;
                }
                if (in_array($table, self::SKIP_DATA, true)) {
                    $report[] = ['table' => $table, 'source' => $this->src()->table($table)->count(), 'target' => 0, 'ok' => true,
                        'note' => 'Bez danych — cache odbuduje się sam (wpisy z SQLite są w formacie nieczytelnym dla MySQL).'];

                    continue;
                }
                $say("Kopiowanie: {$table}");
                $report[] = $this->copyTable($table);
            }
        });

        $ok = collect($report)->every(fn ($r) => $r['ok']);

        return [
            'ok' => $ok,
            'tables' => $report,
            'skipped' => $skipped,
            'seconds' => round(microtime(true) - $started, 1),
            'finished_at' => now()->toDateTimeString(),
        ];
    }

    /** @return array{table: string, source: int, target: int, ok: bool, note: ?string} */
    private function copyTable(string $table): array
    {
        $srcCols = array_map(fn ($c) => $c['name'], Schema::connection($this->source)->getColumns($table));
        $dstCols = array_map(fn ($c) => $c['name'], Schema::connection(self::TARGET)->getColumns($table));
        $cols = array_values(array_intersect($srcCols, $dstCols));
        $missing = array_values(array_diff($srcCols, $dstCols));
        $note = $missing ? 'Pominięte kolumny (brak w bazie docelowej): '.implode(', ', $missing) : null;

        $sourceBefore = $this->src()->table($table)->count();

        try {
            // Kolejne paczki po rowid (nie offset): wiersze dopisane lub usunięte w trakcie nie przesuwają paczek,
            // a dopisane w trakcie (np. dziennik zdarzeń przy ruchu na stronie) też trafiają do kopii.
            $last = 0;
            do {
                $rows = $this->src()->table($table)->selectRaw('rowid as __rowid')->addSelect($cols)
                    ->whereRaw('rowid > ?', [$last])->orderByRaw('rowid')->limit(self::CHUNK)->get()
                    ->map(fn ($r) => (array) $r)->all();
                if ($rows) {
                    $last = (int) end($rows)['__rowid'];
                    $this->dst()->table($table)->insert(array_map(function ($r) {
                        unset($r['__rowid']);

                        return $r;
                    }, $rows));
                }
            } while ($rows !== []); // do pustej paczki — łapie też wiersze dopisane po ostatniej niepełnej
        } catch (Throwable $e) {
            $note = trim(($note ? $note.' ' : '').'Błąd kopiowania: '.self::friendly($e));
        }

        // Porównanie ze stanem źródła PO kopiowaniu — w trakcie mogły przybyć wiersze (serwis działa dalej).
        $sourceCount = $this->src()->table($table)->count();
        $targetCount = $this->dst()->table($table)->count();
        if ($sourceCount !== $sourceBefore) {
            $diff = $sourceCount - $sourceBefore;
            $note = trim(($note ? $note.' ' : '').($diff > 0
                ? "W trakcie kopiowania w SQLite przybyło wierszy: {$diff} (działający serwis) — są też w MySQL."
                : 'W trakcie kopiowania w SQLite ubyło wierszy: '.abs($diff).'.'));
        }

        return ['table' => $table, 'source' => $sourceCount, 'target' => $targetCount, 'ok' => $sourceCount === $targetCount, 'note' => $note];
    }

    private function foreignKeys(bool $on): void
    {
        $this->isMysql()
            ? $this->dst()->statement('SET FOREIGN_KEY_CHECKS='.($on ? 1 : 0))
            : $this->dst()->statement('PRAGMA foreign_keys = '.($on ? 'ON' : 'OFF'));
    }

    private function withoutForeignKeys(callable $fn): void
    {
        if ($this->isMysql()) {
            $this->dst()->statement('SET FOREIGN_KEY_CHECKS=0');
            // Tryb ścisły: za długa wartość (SQLite nie pilnuje długości) to błąd z nazwą kolumny w raporcie, a nie ciche ucięcie.
            $this->dst()->statement("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_AUTO_VALUE_ON_ZERO,NO_ENGINE_SUBSTITUTION'");
        } else {
            $this->dst()->statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $fn();
        } finally {
            $this->isMysql()
                ? $this->dst()->statement('SET FOREIGN_KEY_CHECKS=1')
                : $this->dst()->statement('PRAGMA foreign_keys = ON');
        }
    }

    private function isMysql(): bool
    {
        return in_array($this->dst()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Tabele, w których kolumny tekstowe jako VARCHAR(255) utf8mb4 (do 1020 B każda) przekroczyłyby
     * bezpieczny próg 48 KB z limitu 64 KB na wiersz.
     *
     * @return list<string>
     */
    public function wideTables(): array
    {
        $wide = [];
        foreach ($this->sourceTables() as $table) {
            $strings = collect(Schema::connection($this->source)->getColumns($table))
                ->filter(fn ($c) => preg_match('/^(varchar|char|string)/i', (string) ($c['type_name'] ?? $c['type'] ?? '')))
                ->count();
            if ($strings * 1020 > 48 * 1024) {
                $wide[] = $table;
            }
        }

        return $wide;
    }

    /** @return list<string> */
    private function sourceTables(): array
    {
        return collect($this->src()->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name"))
            ->pluck('name')->values()->all();
    }

    /** @return list<string> */
    private function targetTables(): array
    {
        return collect(Schema::connection(self::TARGET)->getTables())
            ->pluck('name')->reject(fn ($n) => str_starts_with($n, 'sqlite_'))->sort()->values()->all();
    }

    /**
     * Zapisuje połączenie MySQL w .env (kopia poprzedniego pliku obok: .env.przed-mysql-<data>) i czyści cache konfiguracji.
     *
     * @param  array{host: string, port: int|string, database: string, username: string, password: ?string}  $c
     * @return string ścieżka kopii .env
     */
    public static function switchEnv(array $c, ?string $envPath = null): string
    {
        $envPath ??= base_path('.env');
        if (! is_file($envPath)) {
            throw new RuntimeException('Brak pliku .env — nie można przełączyć bazy.');
        }

        $backup = $envPath.'.przed-mysql-'.now()->format('Ymd-His');
        copy($envPath, $backup);

        $values = [
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $c['host'], 'DB_PORT' => (string) $c['port'],
            'DB_DATABASE' => $c['database'], 'DB_USERNAME' => $c['username'], 'DB_PASSWORD' => (string) ($c['password'] ?? ''),
        ];
        $content = (string) file_get_contents($envPath);
        foreach ($values as $key => $value) {
            $line = $key.'='.self::envValue($value);
            $content = preg_match('/^'.$key.'=.*$/m', $content)
                ? preg_replace('/^'.$key.'=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content)
                : rtrim($content)."\n".$line."\n";
        }
        file_put_contents($envPath, $content);

        return $backup;
    }

    /** Najnowsza kopia .env sprzed przełączenia (do „Wróć do SQLite”). */
    public static function latestEnvBackup(?string $envPath = null): ?string
    {
        $envPath ??= base_path('.env');
        $files = glob($envPath.'.przed-mysql-*') ?: [];
        rsort($files);

        return $files[0] ?? null;
    }

    public static function restoreEnv(?string $envPath = null): string
    {
        $envPath ??= base_path('.env');
        $backup = self::latestEnvBackup($envPath);
        if (! $backup) {
            throw new RuntimeException('Brak kopii .env sprzed przełączenia na MySQL.');
        }
        copy($envPath, $envPath.'.przed-powrotem-'.now()->format('Ymd-His'));
        copy($backup, $envPath);

        return $backup;
    }

    private static function envValue(string $v): string
    {
        return preg_match('/[\s#"\'\\\\$=]/', $v) || $v === '' ? '"'.addcslashes($v, '"\\$').'"' : $v;
    }

    /** Plik poprzedniej bazy SQLite (zapas po przełączeniu na MySQL) i jego pliki pomocnicze, które istnieją. @return list<string> */
    public static function oldSqliteFiles(): array
    {
        $main = (string) (config('database.connections.sqlite.database') ?: database_path('database.sqlite'));
        if ($main === ':memory:') {
            return [];
        }

        return array_values(array_filter([$main, $main.'-wal', $main.'-shm', $main.'-journal', $main.'.bak'], 'is_file'));
    }

    /**
     * Usuwa poprzednią bazę SQLite; z $archive najpierw zapisuje skompresowaną kopię w storage/app/sqlite-archive.
     *
     * @return array{deleted: list<string>, bytes: int, archive: ?string}
     */
    public static function deleteOldSqlite(bool $archive = true): array
    {
        $files = self::oldSqliteFiles();
        $bytes = array_sum(array_map('filesize', $files));
        $archivePath = null;

        if ($archive && $files !== []) {
            $dir = storage_path('app/sqlite-archive');
            if (! is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
            $archivePath = $dir.'/'.basename($files[0]).'-'.now()->format('Ymd-His').'.gz';
            $in = fopen($files[0], 'rb');
            $out = gzopen($archivePath, 'wb6');
            if (! $in || ! $out) {
                throw new RuntimeException('Nie udało się zapisać kopii poprzedniej bazy — niczego nie usunięto.');
            }
            while (! feof($in)) {
                gzwrite($out, (string) fread($in, 1 << 20));
            }
            fclose($in);
            gzclose($out);
        }

        foreach ($files as $f) {
            @unlink($f);
        }

        return ['deleted' => array_map('basename', $files), 'bytes' => (int) $bytes, 'archive' => $archivePath];
    }

    /** Czytelny komunikat bez haseł i długich śladów. */
    public static function friendly(Throwable $e): string
    {
        $m = $e->getMessage();
        $m = preg_replace('/\(Connection: .*$/s', '', $m) ?? $m;
        $m = preg_replace('/password=[^;\s)]*/i', 'password=***', $m) ?? $m;

        return trim(mb_substr($m, 0, 400));
    }
}
