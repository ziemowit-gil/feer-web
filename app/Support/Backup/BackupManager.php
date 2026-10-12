<?php

namespace App\Support\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Kopie zapasowe weCMS: tworzenie, lista, retencja, wysyłka na serwer zdalny, przywracanie.
 *
 * Archiwum ZIP:
 *   manifest.json        — wersja formatu, data, host, lista baz, czy są pliki
 *   db/<połączenie>.sqlite | .sql — zrzut każdej bazy (SQLite: VACUUM INTO, spójna kopia bez blokady)
 *   files/…              — zawartość storage/app (bez katalogu kopii i plików tymczasowych)
 *
 * Przywrócenie wymaga tego samego APP_KEY (zaszyfrowane pola w bazie) — dlatego .env
 * nie trafia do kopii, a panel o tym przypomina.
 */
class BackupManager
{
    public const FORMAT = 1;

    /** Rodzaje kopii (część nazwy pliku). */
    public const KINDS = [
        'manual' => 'ręczna',
        'auto' => 'automatyczna',
        'pre-restore' => 'przed przywróceniem',
        'upload' => 'wgrana',
    ];

    private const NAME_PATTERN = '/^backup-\d{4}-\d{2}-\d{2}-\d{6}-(manual|auto|pre-restore|upload)\.zip$/';

    public function __construct(private ?BackupSettings $settings = null)
    {
        $this->settings ??= BackupSettings::load();
    }

    public function directory(): string
    {
        $dir = rtrim(config('backup.path'), '/');

        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        // Zabezpieczenie na wypadek, gdyby katalog trafił kiedyś pod public/.
        if (! is_file($dir.'/.htaccess')) {
            file_put_contents($dir.'/.htaccess', "Require all denied\nDeny from all\n");
        }

        return $dir;
    }

    public static function isValidName(string $name): bool
    {
        return (bool) preg_match(self::NAME_PATTERN, $name);
    }

    /** Pełna ścieżka do archiwum o podanej nazwie albo wyjątek, gdy nazwa/plik niepoprawne. */
    public function pathFor(string $name): string
    {
        if (! self::isValidName($name) || ! is_file($path = $this->directory().'/'.$name)) {
            throw new RuntimeException('Nie ma takiej kopii zapasowej.');
        }

        return $path;
    }

    /**
     * Lista kopii od najnowszej.
     *
     * @return list<array{name: string, kind: string, kind_label: string, size: int, created_at: \Illuminate\Support\Carbon}>
     */
    public function list(): array
    {
        $items = [];

        foreach (glob($this->directory().'/backup-*.zip') ?: [] as $path) {
            $name = basename($path);
            if (! self::isValidName($name)) {
                continue;
            }

            preg_match('/^backup-(\d{4}-\d{2}-\d{2}-\d{6})-([a-z-]+)\.zip$/', $name, $m);
            $items[] = [
                'name' => $name,
                'kind' => $m[2],
                'kind_label' => self::KINDS[$m[2]] ?? $m[2],
                'size' => filesize($path),
                'created_at' => \Illuminate\Support\Carbon::createFromFormat('Y-m-d-His', $m[1]),
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $items;
    }

    /**
     * Tworzy kopię. Zwraca nazwę archiwum.
     *
     * @param  string  $kind  manual|auto|pre-restore
     */
    public function create(string $kind = 'manual', ?bool $includeFiles = null): string
    {
        @set_time_limit(0);
        $includeFiles ??= (bool) $this->settings->get('include_files');

        $dir = $this->directory();
        $name = 'backup-'.now()->format('Y-m-d-His').'-'.$kind.'.zip';
        // Dwie kopie w tej samej sekundzie (np. pre-restore tuż po ręcznej) — dopisz sekundę.
        while (is_file($dir.'/'.$name)) {
            sleep(1);
            $name = 'backup-'.now()->format('Y-m-d-His').'-'.$kind.'.zip';
        }

        $tmpDir = $dir.'/.tmp-'.Str::random(8);
        mkdir($tmpDir, 0700, true);
        $partial = $dir.'/.'.$name.'.part';

        try {
            $databases = $this->dumpDatabases($tmpDir);

            $zip = new ZipArchive;
            if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Nie można utworzyć archiwum w '.$dir);
            }

            foreach ($databases as $db) {
                $zip->addFile($tmpDir.'/'.$db['file'], 'db/'.$db['file']);
            }

            $fileCount = $includeFiles ? $this->addStorageFiles($zip) : 0;

            $zip->addFromString('manifest.json', json_encode([
                'format' => self::FORMAT,
                'app' => 'weCMS',
                'created_at' => now()->toIso8601String(),
                'host' => parse_url((string) config('app.url'), PHP_URL_HOST),
                'kind' => $kind,
                'databases' => $databases,
                'files' => $includeFiles,
                'file_count' => $fileCount,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (! $zip->close()) {
                throw new RuntimeException('Zapis archiwum nie powiódł się (brak miejsca na dysku?).');
            }

            rename($partial, $dir.'/'.$name);
            @chmod($dir.'/'.$name, 0600);
        } finally {
            @unlink($partial);
            $this->removeDirectory($tmpDir);
        }

        activity('cms')->causedBy(auth()->user())->event('backup_created')
            ->withProperty('label', $name)->log('Backup created');

        return $name;
    }

    /** Usuwa najstarsze kopie ponad limit (wgrane się nie liczą, „przed przywróceniem” — max 3). */
    public function prune(): int
    {
        $keep = max(1, (int) $this->settings->get('keep'));
        $rotating = array_values(array_filter($this->list(), fn ($b) => in_array($b['kind'], ['manual', 'auto'], true)));
        $removed = 0;

        // Kopie bezpieczeństwa sprzed przywracania: zostawiamy 3 najnowsze.
        $safety = array_values(array_filter($this->list(), fn ($b) => $b['kind'] === 'pre-restore'));

        foreach (array_merge(array_slice($rotating, $keep), array_slice($safety, 3)) as $old) {
            @unlink($this->directory().'/'.$old['name']);
            $removed++;
        }

        return $removed;
    }

    public function delete(string $name): void
    {
        unlink($this->pathFor($name));

        activity('cms')->causedBy(auth()->user())->event('backup_deleted')
            ->withProperty('label', $name)->log('Backup deleted');
    }

    /** Przyjmuje wgrane archiwum (np. z innego serwera) po sprawdzeniu manifestu. */
    public function import(string $uploadedPath): string
    {
        $this->readManifest($uploadedPath);

        $name = 'backup-'.now()->format('Y-m-d-His').'-upload.zip';
        if (! copy($uploadedPath, $this->directory().'/'.$name)) {
            throw new RuntimeException('Nie udało się zapisać wgranej kopii.');
        }
        @chmod($this->directory().'/'.$name, 0600);

        return $name;
    }

    public function readManifest(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('To nie jest poprawne archiwum ZIP.');
        }

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $zip->close();

        if (! is_array($manifest) || ($manifest['app'] ?? null) !== 'weCMS' || (int) ($manifest['format'] ?? 0) > self::FORMAT) {
            throw new RuntimeException('Archiwum nie jest kopią zapasową weCMS albo pochodzi z nowszej wersji.');
        }

        return $manifest;
    }

    // ---------------------------------------------------------------------------------
    // Serwer zdalny
    // ---------------------------------------------------------------------------------

    /** Wysyła archiwum na serwer zdalny i usuwa tam najstarsze kopie ponad limit. */
    public function upload(string $name): void
    {
        if (! $this->settings->remoteEnabled()) {
            return;
        }

        $disk = Storage::build($this->settings->remoteDiskConfig());
        $stream = fopen($this->pathFor($name), 'rb');

        try {
            $disk->writeStream($name, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $remote = collect($disk->files())
            ->filter(fn ($f) => self::isValidName(basename($f)))
            ->sortDesc()->values();

        foreach ($remote->slice(max(1, (int) $this->settings->get('remote_keep'))) as $old) {
            $disk->delete($old);
        }
    }

    /** Sprawdza połączenie z serwerem zdalnym (zapis i usunięcie pliku testowego). */
    public function testRemote(): void
    {
        if (! $this->settings->remoteEnabled()) {
            throw new RuntimeException('Serwer zdalny nie jest skonfigurowany.');
        }

        $disk = Storage::build($this->settings->remoteDiskConfig());
        $probe = '.wecms-backup-test-'.Str::random(6);
        $disk->put($probe, 'ok');
        $disk->delete($probe);
    }

    // ---------------------------------------------------------------------------------
    // Przywracanie
    // ---------------------------------------------------------------------------------

    /**
     * Przywraca kopię: najpierw robi kopię bieżącego stanu („przed przywróceniem”),
     * potem podmienia bazy i (opcjonalnie) pliki, na końcu uruchamia migracje.
     *
     * @return string nazwa kopii bezpieczeństwa zrobionej przed przywróceniem
     */
    public function restore(string $name, bool $restoreFiles = true): string
    {
        @set_time_limit(0);
        $path = $this->pathFor($name);
        $manifest = $this->readManifest($path);
        $causer = auth()->user();

        $safety = $this->create('pre-restore', $restoreFiles && ! empty($manifest['files']));

        $tmpDir = $this->directory().'/.restore-'.Str::random(8);
        mkdir($tmpDir, 0700, true);

        try {
            $zip = new ZipArchive;
            if ($zip->open($path, ZipArchive::RDONLY) !== true) {
                throw new RuntimeException('Nie można otworzyć archiwum.');
            }

            foreach ($manifest['databases'] ?? [] as $db) {
                $entry = 'db/'.basename($db['file']);
                if (! $zip->extractTo($tmpDir, $entry)) {
                    throw new RuntimeException("Brak zrzutu bazy {$db['connection']} w archiwum.");
                }
                $this->restoreDatabase($db, $tmpDir.'/'.$entry);
            }

            if ($restoreFiles && ! empty($manifest['files'])) {
                $this->restoreFiles($zip);
            }

            $zip->close();
        } finally {
            $this->removeDirectory($tmpDir);
        }

        // Starsza baza może nie mieć nowszych tabel/kolumn.
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('optimize:clear');

        activity('cms')->causedBy($causer)->event('backup_restored')
            ->withProperties(['label' => $name, 'safety' => $safety])->log('Backup restored');

        return $safety;
    }

    // ---------------------------------------------------------------------------------
    // Bazy danych
    // ---------------------------------------------------------------------------------

    /** @return list<array{connection: string, driver: string, file: string}> */
    private function dumpDatabases(string $tmpDir): array
    {
        $result = [];

        foreach (config('backup.connections', []) as $connection) {
            $config = config("database.connections.{$connection}");
            if (! $config) {
                continue;
            }

            $driver = $config['driver'];

            if ($driver === 'sqlite') {
                $source = $config['database'] ?? '';
                if ($source === '' || $source === ':memory:' || ! is_file($source)) {
                    continue;
                }
                $file = $connection.'.sqlite';
                // VACUUM INTO: spójna kopia działającej bazy (także w trybie WAL), bez blokowania zapisów na długo.
                DB::connection($connection)->statement('VACUUM INTO ?', [$tmpDir.'/'.$file]);
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $file = $connection.'.sql';
                $this->run([
                    config('backup.binaries.mysqldump'),
                    '--single-transaction', '--quick', '--routines', '--triggers', '--no-tablespaces',
                    '--default-character-set=utf8mb4',
                    '--host='.$config['host'], '--port='.($config['port'] ?? 3306),
                    '--user='.$config['username'],
                    '--result-file='.$tmpDir.'/'.$file,
                    $config['database'],
                ], ['MYSQL_PWD' => (string) $config['password']]);
            } elseif ($driver === 'pgsql') {
                $file = $connection.'.sql';
                $this->run([
                    config('backup.binaries.pg_dump'), '--clean', '--if-exists', '--no-owner',
                    '--host='.$config['host'], '--port='.($config['port'] ?? 5432),
                    '--username='.$config['username'], '--file='.$tmpDir.'/'.$file,
                    $config['database'],
                ], ['PGPASSWORD' => (string) $config['password']]);
            } else {
                continue;
            }

            $result[] = ['connection' => $connection, 'driver' => $driver, 'file' => $file];
        }

        if ($result === []) {
            throw new RuntimeException('Nie znaleziono żadnej bazy danych do skopiowania.');
        }

        return $result;
    }

    private function restoreDatabase(array $db, string $dumpPath): void
    {
        $connection = $db['connection'];
        $config = config("database.connections.{$connection}");
        if (! $config) {
            return; // połączenia nie ma w tej instalacji — pomijamy
        }

        if ($config['driver'] !== $db['driver']) {
            throw new RuntimeException("Baza {$connection}: kopia z {$db['driver']}, a instalacja używa {$config['driver']}.");
        }

        if ($db['driver'] === 'sqlite') {
            $target = $config['database'];
            DB::disconnect($connection);
            DB::purge($connection);

            // Podmiana atomowa: kopia obok docelowego pliku, potem rename.
            $staging = $target.'.restoring';
            copy($dumpPath, $staging);
            foreach (['-wal', '-shm'] as $suffix) {
                @unlink($target.$suffix);
            }
            rename($staging, $target);

            return;
        }

        if (in_array($db['driver'], ['mysql', 'mariadb'], true)) {
            $this->run([
                config('backup.binaries.mysql'),
                '--host='.$config['host'], '--port='.($config['port'] ?? 3306),
                '--user='.$config['username'], '--default-character-set=utf8mb4',
                $config['database'],
            ], ['MYSQL_PWD' => (string) $config['password']], $dumpPath);
        } elseif ($db['driver'] === 'pgsql') {
            $this->run([
                config('backup.binaries.psql'), '--quiet', '--set=ON_ERROR_STOP=1',
                '--host='.$config['host'], '--port='.($config['port'] ?? 5432),
                '--username='.$config['username'], '--dbname='.$config['database'], '--file='.$dumpPath,
            ], ['PGPASSWORD' => (string) $config['password']]);
        }

        DB::purge($connection);
    }

    // ---------------------------------------------------------------------------------
    // Pliki
    // ---------------------------------------------------------------------------------

    private function storageRoot(): string
    {
        return rtrim(storage_path('app'), '/');
    }

    private function isExcluded(string $relative): bool
    {
        $first = explode('/', $relative, 2)[0];
        if (in_array($first, config('backup.exclude', []), true)) {
            return true;
        }

        // Bazy SQLite w storage (np. blog) trafiają do kopii jako spójny zrzut w db/, nie jako plik.
        return (bool) preg_match('/\.sqlite(-wal|-shm|-journal)?$/', $relative);
    }

    private function addStorageFiles(ZipArchive $zip): int
    {
        $root = $this->storageRoot();
        if (! is_dir($root)) {
            return 0;
        }

        // Katalog kopii może leżeć w storage/app — nigdy nie pakujemy kopii do kopii.
        $backupDir = realpath($this->directory()) ?: $this->directory();
        $store = array_flip(config('backup.store_extensions', []));
        $count = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $real = $file->getRealPath();
            if ($real === false || str_starts_with($real, $backupDir.'/')) {
                continue;
            }

            $relative = ltrim(substr($file->getPathname(), strlen($root)), '/');
            if ($this->isExcluded($relative) || $file->getFilename() === '.gitignore') {
                continue;
            }

            $entry = 'files/'.$relative;
            $zip->addFile($file->getPathname(), $entry);
            if (isset($store[strtolower($file->getExtension())])) {
                $zip->setCompressionName($entry, ZipArchive::CM_STORE);
            }
            $count++;
        }

        return $count;
    }

    /** Rozpakowuje files/ do storage/app (nadpisuje, niczego nie usuwa). */
    private function restoreFiles(ZipArchive $zip): void
    {
        $root = $this->storageRoot();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (! str_starts_with($entry, 'files/') || str_ends_with($entry, '/')) {
                continue;
            }

            $relative = substr($entry, 6);
            // Ochrona przed „zip slip” (../ w nazwach).
            if ($relative === '' || str_contains($relative, '..') || str_starts_with($relative, '/') || $this->isExcluded($relative)) {
                continue;
            }

            $target = $root.'/'.$relative;
            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            $in = $zip->getStream($entry);
            $out = fopen($target, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        }
    }

    // ---------------------------------------------------------------------------------

    private function run(array $command, array $env = [], ?string $stdinFile = null): void
    {
        $process = new Process($command, null, $env, null, 3600);
        if ($stdinFile) {
            $process->setInput(fopen($stdinFile, 'rb'));
        }
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(basename($command[0]).': '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
