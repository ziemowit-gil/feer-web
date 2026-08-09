<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\Modules\ModuleActivateCommand;
use App\Console\Commands\Modules\ModuleDeactivateCommand;
use App\Console\Commands\Modules\ModuleInstallCommand;
use App\Console\Commands\Modules\ModuleListCommand;
use App\Modules\HookManager;
use App\Modules\ModuleManager;
use Illuminate\Support\ServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton HookManager — żyje przez całe żądanie HTTP.
        $this->app->singleton(HookManager::class);

        // Singleton ModuleManager z aktualną ścieżką do modules/.
        $this->app->singleton(
            ModuleManager::class,
            fn ($app) => new ModuleManager(
                app: $app,
                modulesPath: base_path('modules'),
            )
        );
    }

    public function boot(): void
    {
        /** @var ModuleManager $manager */
        $manager = $this->app->make(ModuleManager::class);

        $manager->discover();            // 1. Skanuj dysk
        $manager->loadStatuses();        // 2. Zapytaj bazę (graceful fallback)
        $manager->bootActiveProviders(); // 3. Załaduj aktywne

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleListCommand::class,
                ModuleInstallCommand::class,
                ModuleActivateCommand::class,
                ModuleDeactivateCommand::class,
            ]);
        }
    }
}
