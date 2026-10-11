<?php

namespace Modules\Blog\Http\Middleware;

use App\Http\Middleware\EnsureSiteAvailable;
use App\Models\Page;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Support\BlogSite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Własna domena bloga (np. wiem.feer.org.pl): krótki adres `/{slug}` pokazuje wpis
 * Wiem FEER, jeśli witryna nie ma strony o tym slugu.
 *
 * Dopięty do grupy „web" z priorytetem przed SubstituteBindings — działa po starcie
 * sesji i rozpoznaniu witryny po domenie, ale zanim catch-all stron spróbuje związać
 * {page:slug} (i skończy się 404). Działa wyłącznie dla trasy `page.show` na domenie
 * witryny bloga — na domenie głównej i dla pozostałych tras nic nie zmienia.
 * Trasa nie jest rejestrowana dynamicznie z domeny z bazy, więc cache tras jest bezpieczny.
 */
class ServeBlogOnOwnDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route || $route->getName() !== 'page.show' || ! $request->isMethodSafe()
            || ! BlogSite::servesOwnDomain($request)) {
            return $next($request);
        }

        $slug = $route->parameter('page');
        if (! is_string($slug) || $slug === '') {
            return $next($request);
        }

        try {
            if (Page::forCurrentSite()->where('slug', $slug)->exists()) {
                return $next($request);
            }
            $article = BlogArticle::where('slug', $slug)->first();
        } catch (\Throwable $e) {
            // Brak bazy bloga — zostaje zwykła obsługa (404 strony).
            report($e);

            return $next($request);
        }

        if (! $article) {
            return $next($request);
        }

        $request->attributes->set('blog_site', true);

        // Ten middleware wyprzedza resztę grupy „web" — tryb konserwacji sprawdzamy sami.
        return app(EnsureSiteAvailable::class)->handle(
            $request,
            fn (Request $r) => Router::toResponse($r, app(BlogController::class)->show($r, $article)),
        );
    }
}
