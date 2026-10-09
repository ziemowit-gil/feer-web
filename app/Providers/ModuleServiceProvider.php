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

        // 4. Trasy modułów rejestrują się po routes/web.php, więc przegrywałyby
        //    z catch-allem stron ("/{page:slug}") i grupą sub-witryn ("/{siteSlug}").
        //    Po pełnym starcie przesuwamy je na początek kolekcji (działa też z route:cache).
        $this->app->booted(fn () => $this->prioritizeModuleRoutes());

        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleListCommand::class,
                ModuleInstallCommand::class,
                ModuleActivateCommand::class,
                ModuleDeactivateCommand::class,
            ]);
        }
    }

    /** Trasy, których kontroler leży w `Modules\\…` (lub oznaczone atrybutem grupy `module`), idą przed resztą. */
    private function prioritizeModuleRoutes(): void
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'];
        $all = $router->getRoutes()->getRoutes();

        $isModule = static function (\Illuminate\Routing\Route $route): bool {
            $action = $route->getAction();
            if (! empty($action['module'])) {
                return true;
            }
            $uses = $action['controller'] ?? ($action['uses'] ?? null);

            return is_string($uses) && str_starts_with($uses, 'Modules\\');
        };

        $first = array_filter($all, $isModule);
        if ($first === []) {
            return;
        }

        $collection = new \Illuminate\Routing\RouteCollection();
        foreach ($first as $route) {
            $collection->add($route);
        }
        foreach ($all as $route) {
            if (! $isModule($route)) {
                $collection->add($route);
            }
        }
        $collection->refreshNameLookups();
        $collection->refreshActionLookups();
        $router->setRoutes($collection);
    }
}
