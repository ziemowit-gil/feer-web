<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Crontab użytkownika serwera (System → Harmonogram (cron)): odczyt, diagnoza wpisu harmonogramu feer-web,
 * zapis edytowanej wersji z kopią i przywracanie. Naprawę wpisu robi scripts/cron.sh (te same zasady
 * dla panelu i konsoli). Kopie: storage/app/cron-backups/crontab-<data>.txt.
 */
class CronTab
{
    /** Polecenie crontab — w testach podmieniane na atrapę. */
    public static string $binary = 'crontab';

    public static function backupDir(): string
    {
        return storage_path('app/cron-backups');
    }

    public static function available(): bool
    {
        return function_exists('proc_open');
    }

    public static function read(): string
    {
        $p = new Process([self::$binary, '-l']);
        $p->run();
        // „no crontab for user” — pusty crontab, nie błąd.
        if (! $p->isSuccessful() && ! str_contains(strtolower($p->getErrorOutput()), 'no crontab')) {
            throw new RuntimeException('Nie udało się odczytać crontaba: '.trim($p->getErrorOutput() ?: $p->getOutput()));
        }

        return $p->isSuccessful() ? rtrim($p->getOutput(), "\n") : '';
    }

    /**
     * Zapisuje crontab (kopia poprzedniej wersji w storage/app/cron-backups).
     *
     * @return string ścieżka kopii
     */
    public static function write(string $content): string
    {
        $errors = self::validate($content);
        if ($errors) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $backup = self::backup(self::read());
        $p = new Process([self::$binary, '-']);
        $p->setInput(rtrim(str_replace("\r\n", "\n", $content), "\n")."\n");
        $p->run();
        if (! $p->isSuccessful()) {
            throw new RuntimeException('crontab odrzucił zmiany: '.trim($p->getErrorOutput() ?: $p->getOutput()));
        }

        return $backup;
    }

    public static function backup(string $content): string
    {
        if (! is_dir(self::backupDir())) {
            mkdir(self::backupDir(), 0750, true);
        }
        $path = self::backupDir().'/crontab-'.now()->format('Ymd-His').'-'.substr(md5($content), 0, 6).'.txt';
        file_put_contents($path, $content."\n");

        return $path;
    }

    /** @return list<array{name: string, time: int}> najnowsze pierwsze */
    public static function backups(): array
    {
        $files = glob(self::backupDir().'/crontab-*.txt') ?: [];
        rsort($files);

        return array_map(fn ($f) => ['name' => basename($f), 'time' => (int) filemtime($f)], array_slice($files, 0, 10));
    }

    public static function restore(string $name): string
    {
        $path = self::backupDir().'/'.basename($name);
        if (! preg_match('/^crontab-[0-9-]+(-[a-f0-9]{6})?\.txt$/', basename($name)) || ! is_file($path)) {
            throw new RuntimeException('Nie ma takiej kopii crontaba.');
        }

        return self::write(rtrim((string) file_get_contents($path), "\n"));
    }

    /**
     * Prosta kontrola składni: komentarze, puste linie, zmienne (NAZWA=wartość), @reboot/@daily…
     * i wpisy z 5 polami czasu oraz poleceniem.
     *
     * @return list<string>
     */
    public static function validate(string $content): array
    {
        $errors = [];
        $field = '[0-9*,\/\-a-zA-Z]+';
        foreach (preg_split('/\R/', $content) as $i => $line) {
            $t = trim($line);
            if ($t === '' || str_starts_with($t, '#') || preg_match('/^[A-Za-z_][A-Za-z0-9_]*\s*=/', $t)) {
                continue;
            }
            if (preg_match('/^@(reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)\s+\S/', $t)) {
                continue;
            }
            if (! preg_match('/^'.$field.'\s+'.$field.'\s+'.$field.'\s+'.$field.'\s+'.$field.'\s+\S/', $t)) {
                $errors[] = 'Linia '.($i + 1).': to nie jest poprawny wpis crona (5 pól czasu i polecenie).';
            }
        }

        return $errors;
    }

    /**
     * Diagnoza wpisów harmonogramu feer-web (jak scripts/cron.sh --sprawdz).
     *
     * @return array{expected: string, entries: list<array{line: string, ok: bool, problem: ?string}>, ok: bool, problems: list<string>}
     */
    public static function analyze(string $content, ?string $appDir = null, ?string $phpBin = null): array
    {
        $appDir ??= realpath(base_path()) ?: base_path();
        $expected = '* * * * * cd '.$appDir.' && '.($phpBin ?? self::phpBinary()).' artisan schedule:run >> /dev/null 2>&1';
        $entries = [];
        foreach (preg_split('/\R/', $content) as $line) {
            $t = trim($line);
            if ($t === '' || str_starts_with($t, '#') || ! str_contains($t, $appDir) || ! str_contains($t, 'schedule:run')) {
                continue;
            }
            $problem = null;
            if ($t !== $expected) {
                $cmd = self::commandOf($t);
                $problem = $cmd !== null && ! str_starts_with($cmd, '/')
                    ? "„{$cmd}” bez pełnej ścieżki — w cronie nie działa (np. alias basha)."
                    : 'Wpis różni się od wzorcowego.';
            }
            $entries[] = ['line' => $t, 'ok' => $problem === null, 'problem' => $problem];
        }

        $problems = [];
        if ($entries === []) {
            $problems[] = 'Brak wpisu harmonogramu — kolejka, newsletter i synchronizacje się nie wykonują.';
        }
        if (count($entries) > 1) {
            $problems[] = 'Wpisów harmonogramu jest '.count($entries).' — działające uruchamiają zadania kilka razy.';
        }
        foreach ($entries as $e) {
            if ($e['problem']) {
                $problems[] = $e['problem'];
            }
        }

        return ['expected' => $expected, 'entries' => $entries, 'ok' => $problems === [], 'problems' => $problems];
    }

    /** Pierwsze słowo polecenia po 5 polach czasu, z pominięciem „cd katalog &&”. */
    private static function commandOf(string $line): ?string
    {
        $parts = preg_split('/\s+/', $line);
        for ($i = 5; $i < count($parts); $i++) {
            if ($parts[$i] === 'cd') {
                $i += 2;

                continue;
            }
            if ($parts[$i] === '&&') {
                continue;
            }

            return $parts[$i];
        }

        return null;
    }

    /** PHP CLI dla crona (jak w scripts/cron.sh). */
    public static function phpBinary(): string
    {
        foreach (['/opt/alt/php84/usr/bin/php', '/opt/alt/php85/usr/bin/php'] as $c) {
            if (is_executable($c)) {
                return $c;
            }
        }

        return PHP_BINDIR.'/php';
    }

    /** Naprawa wpisu harmonogramu przez scripts/cron.sh. */
    public static function repair(): string
    {
        $path = getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin';
        if (str_starts_with(self::$binary, '/')) {
            $path = dirname(self::$binary).':'.$path; // atrapa crontaba (testy) przed systemowym
        }
        $p = new Process(['bash', base_path('scripts/cron.sh')], base_path(), ['PATH' => $path, 'PHP_BIN' => self::phpBinary()]);
        $p->setTimeout(60);
        $p->run();
        if (! $p->isSuccessful()) {
            throw new RuntimeException('scripts/cron.sh: '.trim($p->getErrorOutput() ?: $p->getOutput()));
        }

        return trim($p->getOutput());
    }
}
