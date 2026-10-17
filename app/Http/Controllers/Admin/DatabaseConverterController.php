<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Database\SqliteToMysqlConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Throwable;

/**
 * System → Baza MySQL: konwersja bazy głównej z SQLite do MySQL/MariaDB w trzech krokach —
 * sprawdzenie połączenia, konwersja z raportem (liczba wierszy w każdej tabeli), przełączenie .env.
 * Dane połączenia (z hasłem) trzymane zaszyfrowane w cache na 2 godziny, nie w bazie ani w logach.
 * Tylko administrator (trasa w grupie „admin”).
 */
class DatabaseConverterController extends Controller
{
    private const CACHE_TARGET = 'db_convert_target';

    private const CACHE_REPORT = 'db_convert_report';

    public function index(): View
    {
        $target = $this->target();

        return view('admin.database-converter.index', [
            'driver' => config('database.connections.'.config('database.default').'.driver'),
            'target' => $target ? array_merge($target, ['password' => null]) : null,
            'hasPassword' => filled($target['password'] ?? null),
            'check' => Cache::get('db_convert_check'),
            'report' => Cache::get(self::CACHE_REPORT),
            'envBackup' => SqliteToMysqlConverter::latestEnvBackup(),
            'mysqlAvailable' => extension_loaded('pdo_mysql'),
        ]);
    }

    public function check(Request $request): RedirectResponse
    {
        $c = $this->validated($request);

        try {
            $check = (new SqliteToMysqlConverter)->useTarget(SqliteToMysqlConverter::mysqlConfig($c))->check();
        } catch (Throwable $e) {
            return back()->withInput($request->except('password'))->withErrors(['connection' => SqliteToMysqlConverter::friendly($e)]);
        }

        Cache::put(self::CACHE_TARGET, Crypt::encrypt($c), now()->addHours(2));
        Cache::put('db_convert_check', $check, now()->addHours(2));

        return back()->with('status', 'Połączenie działa: '.$check['target_driver'].' '.$check['target_version'].'.');
    }

    public function convert(Request $request): RedirectResponse
    {
        $c = $this->target();
        if (! $c) {
            return back()->withErrors(['connection' => 'Najpierw sprawdź połączenie z bazą MySQL.']);
        }

        @set_time_limit(0);
        ignore_user_abort(true);

        try {
            $report = (new SqliteToMysqlConverter)->useTarget(SqliteToMysqlConverter::mysqlConfig($c))->convert($request->boolean('fresh'));
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['convert' => SqliteToMysqlConverter::friendly($e)]);
        }

        Cache::put(self::CACHE_REPORT, $report, now()->addDays(7));

        return back()->with('status', $report['ok']
            ? 'Konwersja udana — '.count($report['tables']).' tabel w '.$report['seconds'].' s. Możesz przełączyć serwis na MySQL.'
            : 'Konwersja zakończona z różnicami — sprawdź raport poniżej. Serwis dalej działa na SQLite.');
    }

    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Potwierdź, że konwersja była wykonana tuż przed przełączeniem.']);
        $c = $this->target();
        $report = Cache::get(self::CACHE_REPORT);
        if (! $c || ! ($report['ok'] ?? false)) {
            return back()->withErrors(['switch' => 'Przełączyć można dopiero po udanej konwersji.']);
        }

        try {
            $backup = SqliteToMysqlConverter::switchEnv($c);
        } catch (Throwable $e) {
            return back()->withErrors(['switch' => SqliteToMysqlConverter::friendly($e)]);
        }
        Artisan::call('config:clear');
        Cache::forget(self::CACHE_TARGET);

        return redirect()->route('admin.baza-mysql.index')
            ->with('status', 'Serwis działa teraz na MySQL. Kopia poprzedniego .env: '.basename($backup).'. Uruchom ./deploy.sh, aby odświeżyć cache.');
    }

    public function revert(): RedirectResponse
    {
        try {
            $backup = SqliteToMysqlConverter::restoreEnv();
        } catch (Throwable $e) {
            return back()->withErrors(['switch' => SqliteToMysqlConverter::friendly($e)]);
        }
        Artisan::call('config:clear');

        return redirect()->route('admin.baza-mysql.index')
            ->with('status', 'Przywrócono .env z '.basename($backup).' — serwis wrócił do SQLite. Zmiany zapisane w MySQL po przełączeniu nie zostały przeniesione.');
    }

    /** @return array{host: string, port: int, database: string, username: string, password: ?string} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$-]+$/'],
            'username' => ['required', 'string', 'max:80'],
            'password' => ['nullable', 'string', 'max:255'],
        ], [
            'database.regex' => 'Nazwa bazy może zawierać tylko litery, cyfry, podkreślenie, myślnik i $.',
        ], [
            'host' => 'host', 'port' => 'port', 'database' => 'nazwa bazy', 'username' => 'użytkownik', 'password' => 'hasło',
        ]);

        // Puste hasło przy ponownym sprawdzeniu = poprzednie (pole hasła nie jest wypełniane w formularzu).
        if (blank($data['password'] ?? null) && ($prev = $this->target())) {
            $data['password'] = $prev['password'];
        }
        $data['port'] = (int) $data['port'];

        return $data;
    }

    private function target(): ?array
    {
        $enc = Cache::get(self::CACHE_TARGET);
        if (! $enc) {
            return null;
        }

        try {
            return Crypt::decrypt($enc);
        } catch (Throwable) {
            return null;
        }
    }
}
