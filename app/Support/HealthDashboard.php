<?php

namespace App\Support;

use App\Support\Backup\BackupManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

/**
 * Sekcje pulpitu „Kondycja serwisu” (System → Kondycja serwisu): harmonogram, kolejka, ostatni deploy,
 * kopie zapasowe, baza i błędy z ostatniej doby — to, co dotąd sprawdzało się przez SSH.
 * Każda sekcja: ['key', 'title', 'icon', 'status' (ok|warning|error), 'summary', 'rows' => [etykieta => wartość],
 * 'table' => ?['head' => [...], 'rows' => [[...]]], 'link' => ?['url', 'label'], 'notes' => [...]].
 */
class HealthDashboard
{
    /** Kopia starsza niż tyle dni = ostrzeżenie. */
    public const BACKUP_MAX_AGE_DAYS = 2;

    /** @return list<array<string, mixed>> */
    public function sections(): array
    {
        return array_map(fn (string $m) => $this->safe($m), ['scheduler', 'queue', 'deploy', 'backups', 'database', 'errors']);
    }

    /** Jedna sekcja nie może położyć całej strony. */
    private function safe(string $method): array
    {
        try {
            return $this->{$method}();
        } catch (\Throwable $e) {
            return ['key' => $method, 'title' => ucfirst($method), 'icon' => 'fa-circle-exclamation', 'status' => 'warning',
                'summary' => 'Nie udało się sprawdzić: '.mb_substr($e->getMessage(), 0, 200), 'rows' => [], 'table' => null, 'link' => null, 'notes' => []];
        }
    }

    public function scheduler(): array
    {
        $last = DeployHealth::lastHeartbeat();
        $minutes = $last !== null ? (int) floor((now()->getTimestamp() - $last) / 60) : null;
        $status = $minutes === null || $minutes > DeployHealth::HEARTBEAT_MAX_AGE_MINUTES ? 'error' : 'ok';
        $rows = ['Ostatnie uruchomienie' => $last ? Carbon::createFromTimestamp($last)->timezone(config('app.timezone'))->format('d.m.Y H:i:s').' ('.($minutes < 1 ? 'przed chwilą' : $minutes.' min temu').')' : 'brak sygnału'];
        $notes = [];

        if (CronTab::available()) {
            try {
                $analysis = CronTab::analyze(CronTab::read());
                $rows['Wpis w crontabie'] = $analysis['ok'] ? 'jeden, poprawny' : implode(' ', $analysis['problems']);
                if (! $analysis['ok'] && $status === 'ok') {
                    $status = 'warning';
                }
            } catch (\Throwable $e) {
                $rows['Wpis w crontabie'] = 'nie udało się odczytać crontaba';
            }
        }
        if ($status === 'error') {
            $notes[] = 'Bez harmonogramu nie działają kolejka (maile, newsletter), synchronizacje i publikacja treści. Napraw wpis w „Harmonogram (cron)” albo uruchom scripts/cron.sh.';
        }

        return $this->section('scheduler', 'Harmonogram', 'fa-clock', $status,
            $status === 'ok' ? 'Działa' : ($status === 'warning' ? 'Działa, ale crontab wymaga uwagi' : 'Nie działa'),
            $rows, null, Route::has('admin.cron.index') ? ['url' => route('admin.cron.index'), 'label' => 'Harmonogram (cron)'] : null, $notes);
    }

    public function queue(): array
    {
        $class = '\\Modules\\Newsletter\\Services\\QueueHealth';
        if (! class_exists($class)) {
            return $this->section('queue', 'Kolejka zadań', 'fa-list-check', 'ok', 'Moduł kolejki (Newsletter) nie jest zainstalowany', []);
        }
        $s = app($class)->snapshot();
        $status = $s['ok'] ? 'ok' : (($s['pending'] > 0 && $s['oldest']) ? 'error' : 'warning');
        $rows = [
            'Czeka w kolejce' => (string) $s['pending'].($s['oldest'] ? ' (najstarsze od '.$s['oldest']->diffForHumans().')' : ''),
            'Nieudane w ostatniej dobie' => (string) $s['failed_recent'],
            'Nieudane łącznie' => (string) $s['failed'],
            'Ostatnia praca workera' => $s['worker'] ? $s['worker']->timezone(config('app.timezone'))->format('d.m.Y H:i').' ('.$s['worker']->diffForHumans().')' : 'brak danych',
        ];

        return $this->section('queue', 'Kolejka zadań', 'fa-list-check', $status,
            $s['ok'] ? 'Działa' : implode(' ', $s['problems']), $rows, null,
            Route::has('admin.newsletter.dashboard') ? ['url' => route('admin.newsletter.dashboard'), 'label' => 'Pulpit newslettera'] : null);
    }

    public function deploy(?string $logDir = null): array
    {
        $logDir ??= storage_path('logs');
        $files = glob($logDir.'/deploy-*.log') ?: [];
        rsort($files);
        $rows = ['Bieżący commit' => $this->currentCommit()];

        if ($files === []) {
            return $this->section('deploy', 'Ostatnie wdrożenie', 'fa-rocket', 'warning', 'Brak logu wdrożenia — uruchom ./deploy.sh --tak na serwerze', $rows);
        }

        $info = self::parseDeployLog($files[0]);
        $rows = ['Kiedy' => $info['at'] ? $info['at']->format('d.m.Y H:i').' ('.$info['at']->diffForHumans().')' : basename($files[0])]
            + ($info['commit'] ? ['Wdrożony commit' => $info['commit']] : [])
            + ($info['duration'] ? ['Czas' => $info['duration']] : [])
            + $rows
            + ['Log' => 'storage/logs/'.basename($files[0])];
        $status = match ($info['result']) { 'ok' => 'ok', 'fail' => 'error', default => 'warning' };
        $summary = match ($info['result']) {
            'ok' => 'Zakończone pomyślnie',
            'fail' => 'NIEUDANE'.($info['failed_step'] ? ' — krok „'.$info['failed_step'].'”' : ''),
            default => 'W toku albo przerwane bez podsumowania',
        };

        return $this->section('deploy', 'Ostatnie wdrożenie', 'fa-rocket', $status, $summary, $rows);
    }

    /** @return array{at: ?Carbon, result: string, commit: ?string, duration: ?string, failed_step: ?string} */
    public static function parseDeployLog(string $path): array
    {
        $text = (string) @file_get_contents($path);
        $text = preg_replace('/\e\[[0-9;]*m/', '', $text) ?? $text; // kolory terminala
        // Czas: znacznik „# start: <epoka>” z deploy.sh (strefa serwera ≠ strefa aplikacji), inaczej nazwa pliku w strefie serwera.
        if (preg_match('/^# start: (\d+)/m', $text, $m)) {
            $at = Carbon::createFromTimestamp((int) $m[1])->timezone(config('app.timezone'));
        } else {
            $at = preg_match('/deploy-(\d{8}-\d{6})\.log$/', $path, $m)
                ? Carbon::createFromFormat('Ymd-His', $m[1], date_default_timezone_get() === 'UTC' ? 'Europe/Warsaw' : date_default_timezone_get())->timezone(config('app.timezone'))
                : null;
        }
        $result = 'unknown';
        $commit = null;
        $duration = null;
        $failed = null;

        // Wynik zapisany przez deploy.sh w logu („# wynik: OK | commit: … | czas: …”).
        if (preg_match('/^# wynik: OK \| commit: (\S+) \| czas: ([^|]+)/m', $text, $m)) {
            return ['at' => $at, 'result' => 'ok', 'commit' => $m[1], 'duration' => trim($m[2]), 'failed_step' => null];
        }
        if (preg_match('/^# wynik: NIEUDANE \| krok: ([^|]+) \| czas: ([^|]+)/m', $text, $m)) {
            return ['at' => $at, 'result' => 'fail', 'commit' => null, 'duration' => trim($m[2]), 'failed_step' => trim($m[1])];
        }

        if (preg_match('/Wdrożenie zakończone\S*\s+—\s+(\S+)\s+w\s+(.+)$/mu', $text, $m)) {
            [$result, $commit, $duration] = ['ok', $m[1], trim($m[2])];
        } elseif (preg_match('/Wdrożenie NIEUDANE\S*\s+—\s+krok\s+[\d\/]+\s+„([^”]+)”.*?,\s*([^)]+)\)/mu', $text, $m)) {
            [$result, $failed, $duration] = ['fail', $m[1], trim($m[2])];
        } elseif (str_contains($text, 'Deploy NIEUDANY') || str_contains($text, 'Wdrożenie NIEUDANE')) {
            $result = 'fail';
        } elseif (str_contains($text, 'Deploy zakończony pomyślnie')) {
            $result = 'ok';
        }
        if (! $commit && preg_match_all('/Kod (?:zaktualizowany|pobrany) \(([0-9a-f]{7,})\)/u', $text, $mm)) {
            $commit = end($mm[1]);
        }

        return ['at' => $at, 'result' => $result, 'commit' => $commit, 'duration' => $duration, 'failed_step' => $failed];
    }

    private function currentCommit(): string
    {
        try {
            $p = new Process(['git', 'log', '-1', '--format=%h · %s · %cr'], base_path());
            $p->setTimeout(5);
            $p->run();
            if ($p->isSuccessful() && trim($p->getOutput()) !== '') {
                return mb_strimwidth(trim($p->getOutput()), 0, 140, '…');
            }
        } catch (\Throwable) {
        }
        // Bez gita w PATH: odczyt .git/HEAD.
        $head = @file_get_contents(base_path('.git/HEAD'));
        if ($head && preg_match('/ref: (.+)/', $head, $m) && ($ref = @file_get_contents(base_path('.git/'.trim($m[1]))))) {
            return substr(trim($ref), 0, 8);
        }

        return 'nieznany';
    }

    public function backups(?BackupManager $manager = null): array
    {
        $manager ??= app(BackupManager::class);
        $list = $manager->list();
        $link = Route::has('admin.kopie.index') ? ['url' => route('admin.kopie.index'), 'label' => 'Kopie zapasowe'] : null;
        if ($list === []) {
            return $this->section('backups', 'Kopie zapasowe', 'fa-box-archive', 'warning', 'Brak kopii zapasowych', ['Liczba kopii' => '0'], null, $link,
                ['Zrób kopię ręcznie i włącz harmonogram kopii w „Kopie zapasowe”.']);
        }
        $latest = $list[0];
        $old = $latest['created_at']->lt(now()->subDays(self::BACKUP_MAX_AGE_DAYS));
        $total = array_sum(array_column($list, 'size'));

        return $this->section('backups', 'Kopie zapasowe', 'fa-box-archive', $old ? 'warning' : 'ok',
            $old ? 'Ostatnia kopia jest starsza niż '.self::BACKUP_MAX_AGE_DAYS.' dni' : 'Aktualne',
            [
                'Ostatnia' => $latest['created_at']->format('d.m.Y H:i').' ('.$latest['created_at']->diffForHumans().'), '.$latest['kind_label'].', '.self::bytes($latest['size']),
                'Liczba kopii' => (string) count($list),
                'Łączny rozmiar' => self::bytes($total),
            ], null, $link);
    }

    public function database(): array
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        $rows = ['Sterownik' => match ($driver) { 'mysql' => 'MySQL', 'mariadb' => 'MariaDB', 'sqlite' => 'SQLite', default => $driver }];
        $table = null;

        if ($driver === 'sqlite') {
            $path = $conn->getDatabaseName();
            $rows['Rozmiar'] = is_file($path) ? self::bytes((int) filesize($path)) : 'nieznany';
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $db = $conn->getDatabaseName();
            $rows['Baza'] = $db;
            $size = $conn->selectOne('SELECT SUM(data_length + index_length) AS s FROM information_schema.TABLES WHERE table_schema = ?', [$db]);
            $rows['Rozmiar'] = self::bytes((int) ($size->s ?? 0));
            $top = $conn->select('SELECT table_name AS t, table_rows AS r, data_length + index_length AS s FROM information_schema.TABLES WHERE table_schema = ? ORDER BY s DESC LIMIT 8', [$db]);
            $table = ['head' => ['Tabela', 'Wiersze (szac.)', 'Rozmiar'], 'rows' => array_map(fn ($r) => [$r->t, number_format((int) $r->r, 0, ',', ' '), self::bytes((int) $r->s)], $top)];
        }

        try {
            $blog = DB::connection('blog');
            if ($blog->getDriverName() === 'sqlite') {
                $path = $blog->getDatabaseName();
                $rows['Baza bloga (SQLite)'] = is_file($path) ? self::bytes((int) filesize($path)) : 'brak pliku';
            }
        } catch (\Throwable) {
        }

        return $this->section('database', 'Baza danych', 'fa-database', 'ok', $rows['Rozmiar'] ?? 'połączono', $rows, $table);
    }

    public function errors(?string $logPath = null): array
    {
        $d = LogDigest::read($logPath);
        $notes = [];
        if ($d['exists'] && $d['size'] > LogDigest::ROTATE_HINT_BYTES) {
            $notes[] = 'Plik laravel.log ma '.self::bytes($d['size']).' — warto włączyć rotację (LOG_CHANNEL=daily w .env), żeby logi nie rosły bez końca.';
        }
        if ($d['truncated']) {
            $notes[] = 'Analizujemy ostatnie '.self::bytes(LogDigest::TAIL_BYTES).' logu.';
        }
        if (! $d['exists']) {
            return $this->section('errors', 'Błędy z ostatniej doby', 'fa-bug', 'ok', 'Brak pliku logu', [], null, null, $notes);
        }

        $status = $d['total'] === 0 ? 'ok' : ($d['total'] >= 20 ? 'error' : 'warning');
        $table = $d['groups'] === [] ? null : [
            'head' => ['Błąd', 'Wystąpienia', 'Ostatnio'],
            'rows' => array_map(fn ($g) => [
                $g['message'].($g['frame'] ? ' — '.$g['frame'] : ''),
                (string) $g['count'],
                $g['last']->format('d.m H:i'),
            ], $d['groups']),
        ];

        return $this->section('errors', 'Błędy z ostatniej doby', 'fa-bug', $status,
            $d['total'] === 0 ? 'Brak błędów' : $d['total'].' '.($d['total'] === 1 ? 'błąd' : 'błędów').' w '.count($d['groups']).' '.(count($d['groups']) === 1 ? 'rodzaju' : 'rodzajach'),
            ['Rozmiar logu' => self::bytes($d['size'])], $table, null, $notes);
    }

    private function section(string $key, string $title, string $icon, string $status, string $summary, array $rows, ?array $table = null, ?array $link = null, array $notes = []): array
    {
        return compact('key', 'title', 'icon', 'status', 'summary', 'rows', 'table', 'link', 'notes');
    }

    public static function bytes(int $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $i => $unit) {
            if ($b < 1024 ** ($i + 1) || $unit === 'GB') {
                return $i === 0 ? $b.' B' : number_format($b / 1024 ** $i, 1, ',', ' ').' '.$unit;
            }
        }

        return $b.' B';
    }
}
