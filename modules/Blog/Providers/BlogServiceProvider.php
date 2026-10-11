<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Blog\Console\CreateBlogSiteCommand;
use Modules\Blog\Http\Middleware\BindBlogSite;

final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('blog.site', BindBlogSite::class);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'blog');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([CreateBlogSiteCommand::class]);
        }
    }
}
