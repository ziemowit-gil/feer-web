<?php

declare(strict_types=1);

namespace App\Console\Commands\Modules;

use App\Modules\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class ModuleInstallCommand extends Command
{
    protected $signature   = 'module:install {identifier : Identyfikator modułu (nazwa katalogu, małe litery)}';
    protected $description = 'Zainstaluj moduł: zarejestruj w bazie i uruchom jego migracje';

    public function handle(ModuleManager $manager): int
    {
        $identifier = $this->argument('identifier');
        $manifest   = $manager->get($identifier);

        if ($manifest === null) {
            $this->error("Moduł [{$identifier}] nie istnieje w katalogu modules/.");
            $this->line('Dostępne moduły: ' . $manager->all()->keys()->implode(', '));
            return self::FAILURE;
        }

        // Rejestracja w tabeli plugins (status = inactive)
        $manager->install($manifest);
        $this->info("Moduł [{$manifest->name} v{$manifest->version}] zarejestrowany w bazie.");

        // Uruchomienie migracji modułu
        if ($manifest->hasMigrations) {
            $migrationsPath = $manifest->path('database', 'migrations');

            if (is_dir($migrationsPath)) {
                $this->info('Uruchamianie migracji...');
                Artisan::call('migrate', [
                    '--path'     => $migrationsPath,
                    '--realpath' => true,
                    '--force'    => true,
                ]);
                $this->output->write(Artisan::output());
            }
        }

        $this->line('');
        $this->info("Gotowe. Aktywuj poleceniem:");
        $this->line("  php artisan module:activate {$identifier}");

        return self::SUCCESS;
    }
}
