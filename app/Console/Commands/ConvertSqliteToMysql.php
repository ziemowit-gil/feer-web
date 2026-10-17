<?php

namespace App\Console\Commands;

use App\Support\Database\SqliteToMysqlConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Konwersja bazy głównej SQLite → MySQL/MariaDB z konsoli (to samo co System → Baza MySQL w panelu).
 *
 *   php84 artisan db:konwertuj-mysql --host=localhost --database=feer --username=feer          (hasło: pytanie)
 *   php84 artisan db:konwertuj-mysql … --zastap --przelacz                                      (od nowa + .env)
 */
class ConvertSqliteToMysql extends Command
{
    protected $signature = 'db:konwertuj-mysql
        {--host=127.0.0.1 : Host MySQL}
        {--port=3306 : Port MySQL}
        {--database= : Nazwa bazy docelowej (pusta)}
        {--username= : Użytkownik MySQL}
        {--password= : Hasło (bez opcji — pytanie bez echa)}
        {--sprawdz : Tylko sprawdź połączenie}
        {--zastap : Usuń tabele w bazie docelowej i utwórz je od nowa}
        {--przelacz : Po udanej konwersji zapisz połączenie MySQL w .env}';

    protected $description = 'Przenosi bazę główną z SQLite do MySQL/MariaDB (schemat z migracji, dane, porównanie liczby wierszy)';

    public function handle(): int
    {
        $c = [
            'host' => (string) $this->option('host'),
            'port' => (int) $this->option('port'),
            'database' => (string) ($this->option('database') ?: $this->ask('Nazwa bazy MySQL')),
            'username' => (string) ($this->option('username') ?: $this->ask('Użytkownik MySQL')),
            'password' => $this->option('password') ?? (string) $this->secret('Hasło MySQL'),
        ];

        $converter = (new SqliteToMysqlConverter)->useTarget(SqliteToMysqlConverter::mysqlConfig($c));

        try {
            $check = $converter->check();
            $this->info("Połączono: {$check['target_driver']} {$check['target_version']} · tabel w bazie docelowej: {$check['target_tables']}");
            $this->line("Źródło SQLite: {$check['source_tables']} tabel, {$check['source_rows']} wierszy.");
            if ($this->option('sprawdz')) {
                return self::SUCCESS;
            }

            $report = $converter->convert((bool) $this->option('zastap'), fn ($m) => $this->line('  '.$m));
        } catch (Throwable $e) {
            $this->error(SqliteToMysqlConverter::friendly($e));

            return self::FAILURE;
        }

        $this->table(['Tabela', 'SQLite', 'MySQL', 'Wynik', 'Uwagi'], array_map(fn ($r) => [
            $r['table'], $r['source'], $r['target'], $r['ok'] ? 'OK' : 'RÓŻNICA', $r['note'] ?? '',
        ], $report['tables']));
        if ($report['skipped']) {
            $this->warn('Tabele bez odpowiednika w migracjach (pominięte): '.implode(', ', $report['skipped']));
        }

        if (! $report['ok']) {
            $this->error("Konwersja zakończona z różnicami ({$report['seconds']} s) — nie przełączam bazy.");

            return self::FAILURE;
        }
        $this->info("Konwersja udana ({$report['seconds']} s).");

        if ($this->option('przelacz')) {
            $backup = SqliteToMysqlConverter::switchEnv($c);
            Artisan::call('config:clear');
            $this->info('Przełączono .env na MySQL. Kopia poprzedniego pliku: '.basename($backup));
            $this->line('Uruchom teraz ./deploy.sh albo: php84 artisan optimize');
        }

        return self::SUCCESS;
    }
}
