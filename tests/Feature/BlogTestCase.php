<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Providers\BlogServiceProvider;
use Tests\TestCase;

/**
 * Wspólna baza testów bloga „Wiem FEER".
 *
 * Blog ma własne połączenie `blog` (osobny plik SQLite), którego RefreshDatabase
 * nie migruje — w testach podmieniamy je na bazę w pamięci i uruchamiamy migracje
 * z database/migrations/blog. Moduł nie jest wbudowany (built_in), więc po migracji
 * głównej bazy instalujemy go i aktywujemy, rejestrujemy provider i — jak
 * ModuleServiceProvider — przesuwamy trasy modułu przed catch-all stron.
 */
abstract class BlogTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        config(['database.connections.blog.database' => ':memory:']);
        DB::purge('blog');
        $this->artisan('migrate', ['--database' => 'blog', '--path' => 'database/migrations/blog'])->assertSuccessful();

        $modules = $this->app->make(ModuleManager::class);
        if (! $modules->isActive('blog')) {
            $modules->install($modules->get('blog'));
            $modules->activate('blog');
            $this->app->register(BlogServiceProvider::class, true);
            $this->prioritizeBlogRoutes();
            // Konsola jest już zbudowana — komendę modułu dopisujemy ręcznie.
            $this->app->make(\Illuminate\Contracts\Console\Kernel::class)
                ->registerCommand($this->app->make(\Modules\Blog\Console\CreateBlogSiteCommand::class));
        }
    }

    private function prioritizeBlogRoutes(): void
    {
        $router = $this->app['router'];
        $all = $router->getRoutes()->getRoutes();
        $isBlog = static fn ($route) => str_starts_with((string) ($route->getAction()['controller'] ?? ''), 'Modules\\Blog\\');

        $collection = new RouteCollection();
        foreach (array_filter($all, $isBlog) as $route) {
            $collection->add($route);
        }
        foreach ($all as $route) {
            if (! $isBlog($route)) {
                $collection->add($route);
            }
        }
        $collection->refreshNameLookups();
        $collection->refreshActionLookups();
        $router->setRoutes($collection);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** @param array<string, mixed> $attributes */
    protected function article(string $title, array $attributes = []): BlogArticle
    {
        return BlogArticle::create(array_merge([
            'title'        => $title,
            'slug'         => \Illuminate\Support\Str::slug($title),
            'body'         => '<p>Treść artykułu ' . $title . '.</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $attributes));
    }
}
