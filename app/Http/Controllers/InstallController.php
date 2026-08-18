<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\LicenseValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Throwable;

class InstallController extends Controller
{
    const STEPS = [
        'welcome'      => ['title' => 'Witamy',        'icon' => 'fa-house'],
        'requirements' => ['title' => 'Wymagania',     'icon' => 'fa-list-check'],
        'configure'    => ['title' => 'Konfiguracja',  'icon' => 'fa-sliders'],
        'done'         => ['title' => 'Gotowe',         'icon' => 'fa-circle-check'],
    ];

    public function index()
    {
        $step = session('install_step', 'welcome');
        return $this->render($step);
    }

    public function post(Request $request)
    {
        $step = $request->input('_step', 'welcome');

        return match ($step) {
            'welcome'      => $this->handleWelcome($request),
            'requirements' => $this->handleRequirements($request),
            'configure'    => $this->handleConfigure($request),
            'demo'         => $this->handleDemo($request),
            default        => redirect()->route('install.index'),
        };
    }

    // ─── Step handlers ───────────────────────────────────────────────────────

    private function handleWelcome(Request $request)
    {
        session(['install_step' => 'requirements']);
        return redirect()->route('install.index');
    }

    private function handleRequirements(Request $request)
    {
        $checks = $this->requirements();
        $failed = collect($checks)->where('pass', false)->count();

        if ($failed > 0) {
            session(['install_step' => 'requirements']);
            return redirect()->route('install.index')
                ->withErrors(['requirements' => 'Napraw błędy wymagań przed kontynuowaniem.']);
        }

        session(['install_step' => 'configure']);
        return redirect()->route('install.index');
    }

    private function handleConfigure(Request $request)
    {
        // Walidacja licencji (pomijana w trybie dev gdy LICENSE_PUBLIC_KEY nie ustawiony)
        if (! LicenseValidator::isDevMode()) {
            $licenseError = LicenseValidator::errorMessage(
                $request->input('license_key', ''),
                $request->input('app_url', config('app.url'))
            );
            if ($licenseError) {
                return back()->withInput()->withErrors(['license_key' => $licenseError]);
            }
        }

        $request->validate([
            'db_type'          => ['required', 'in:sqlite,mysql'],
            'db_host'          => ['required_if:db_type,mysql', 'nullable', 'string'],
            'db_port'          => ['required_if:db_type,mysql', 'nullable', 'integer'],
            'db_name'          => ['required_if:db_type,mysql', 'nullable', 'string'],
            'db_user'          => ['required_if:db_type,mysql', 'nullable', 'string'],
            'db_pass'          => ['nullable', 'string'],
            'admin_name'       => ['required', 'string', 'max:100'],
            'admin_email'      => ['required', 'email'],
            'admin_password'   => ['required', 'confirmed', Password::min(8)],
            'site_name'        => ['required', 'string', 'max:120'],
            'site_tagline'     => ['nullable', 'string', 'max:200'],
            'site_template'    => ['required', 'in:default,ngo,municipality'],
        ]);

        try {
            // 1. Klucz aplikacji
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // 2. Konfiguracja bazy danych
            if ($request->db_type === 'sqlite') {
                $dbPath = database_path('database.sqlite');
                if (! file_exists($dbPath)) {
                    touch($dbPath);
                }
                $this->setEnvValue('DB_CONNECTION', 'sqlite');
                $this->setEnvValue('DB_DATABASE', $dbPath);
            } else {
                $this->setEnvValue('DB_CONNECTION', 'mysql');
                $this->setEnvValue('DB_HOST', $request->db_host);
                $this->setEnvValue('DB_PORT', $request->db_port ?? '3306');
                $this->setEnvValue('DB_DATABASE', $request->db_name);
                $this->setEnvValue('DB_USERNAME', $request->db_user);
                $this->setEnvValue('DB_PASSWORD', $request->db_pass ?? '');
            }

            // 3. Migracje
            Artisan::call('migrate', ['--force' => true]);

            // 4. Konto admina
            User::create([
                'name'     => $request->admin_name,
                'email'    => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role'     => 'admin',
            ]);

            // 5. Ustawienia serwisu
            $settings = SiteSetting::first() ?? new SiteSetting();
            $settings->fill([
                'site_name'     => $request->site_name,
                'tagline'       => $request->site_tagline,
                'site_template' => $request->site_template,
            ])->save();

            // 6. Dane demonstracyjne (opcjonalne)
            if ($request->boolean('seed_demo')) {
                Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
            }

            // 7. Plik blokady
            file_put_contents(
                storage_path('app/installed.lock'),
                date('c')
            );

            Artisan::call('config:clear');
            Artisan::call('view:clear');

            session([
                'install_step' => 'done',
                'install_error' => null,
                'install_demo_seeded' => $request->boolean('seed_demo'),
            ]);
            return redirect()->route('install.index');

        } catch (Throwable $e) {
            return redirect()->route('install.index')
                ->withErrors(['install' => $e->getMessage()]);
        }
    }

    private function handleDemo(Request $request)
    {
        try {
            Artisan::call('db:seed', [
                '--class' => 'DemoSeeder',
                '--force' => true,
            ]);
        } catch (Throwable $e) {
            return redirect()->route('install.index')
                ->withErrors(['demo' => $e->getMessage()]);
        }

        return redirect()->route('install.index')->with('demo_done', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function render(string $step)
    {
        $steps        = self::STEPS;
        $stepKeys     = array_keys($steps);
        $currentIdx   = array_search($step, $stepKeys);
        $requirements = $step === 'requirements' ? $this->requirements() : null;
        $demoMode     = (bool) request()->query('demo', session('install_demo_mode', false));
        $demoSeeded   = session('install_demo_seeded', false);

        if ($demoMode) {
            session(['install_demo_mode' => true]);
        }

        return view('install.wizard', compact(
            'step', 'steps', 'stepKeys', 'currentIdx', 'requirements', 'demoMode', 'demoSeeded'
        ));
    }

    private function requirements(): array
    {
        return [
            ['label' => 'PHP ≥ 8.3',             'pass' => version_compare(PHP_VERSION, '8.3.0', '>='), 'detail' => PHP_VERSION],
            ['label' => 'Rozszerzenie PDO',       'pass' => extension_loaded('pdo'),         'detail' => null],
            ['label' => 'PDO SQLite',             'pass' => extension_loaded('pdo_sqlite'),   'detail' => null],
            ['label' => 'OpenSSL',                'pass' => extension_loaded('openssl'),      'detail' => null],
            ['label' => 'Mbstring',               'pass' => extension_loaded('mbstring'),     'detail' => null],
            ['label' => 'Tokenizer',              'pass' => extension_loaded('tokenizer'),    'detail' => null],
            ['label' => 'XML',                    'pass' => extension_loaded('xml'),          'detail' => null],
            ['label' => 'Fileinfo',               'pass' => extension_loaded('fileinfo'),     'detail' => null],
            ['label' => 'GD lub Imagick',         'pass' => extension_loaded('gd') || extension_loaded('imagick'), 'detail' => null],
            ['label' => 'storage/ zapisywalny',   'pass' => is_writable(storage_path()),      'detail' => storage_path()],
            ['label' => 'bootstrap/cache/ zapis', 'pass' => is_writable(base_path('bootstrap/cache')), 'detail' => null],
            ['label' => '.env zapisywalny',        'pass' => is_writable(base_path('.env')),  'detail' => null],
        ];
    }

    private function setEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$value}";
        }

        file_put_contents($envPath, $content);

        // Odśwież runtime
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
