<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Console\CreateBlogSiteCommand;
use Modules\Blog\Http\Middleware\BindBlogSite;
use Modules\Blog\Http\Middleware\ServeBlogOnOwnDomain;

final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('blog.site', BindBlogSite::class);

        // Własna domena bloga: /{slug} → wpis (gdy nie ma takiej strony). Musi działać
        // po sesji, a przed SubstituteBindings (wiązaniem {page:slug}) — stąd priorytet.
        // Przez jądro HTTP (nie sam router) — jądro przy każdej synchronizacji nadpisuje
        // grupy i priorytety routera, więc dopisana tam zmiana by przepadła.
        $kernel = $this->app->make(HttpKernel::class);
        if ($kernel instanceof \Illuminate\Foundation\Http\Kernel) {
            $kernel->appendMiddlewareToGroup('web', ServeBlogOnOwnDomain::class);
            $kernel->addToMiddlewarePriorityBefore(SubstituteBindings::class, ServeBlogOnOwnDomain::class);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'blog');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([CreateBlogSiteCommand::class]);
        }
    }
}
