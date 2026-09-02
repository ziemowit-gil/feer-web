<?php

use App\Http\Middleware\EnsureCanManageSites;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureSiteAvailable;
use App\Http\Middleware\EnsureSiteBySlug;
use App\Http\Middleware\EnsureTwoFactorSetup;
use App\Http\Middleware\EnsureUserCanAccessModule;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\MinifyHtmlResponse;
use App\Http\Middleware\ResolveAdminActiveSite;
use App\Http\Middleware\ResolveSiteByDomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'module' => EnsureModuleEnabled::class,
            'module-access' => EnsureUserCanAccessModule::class,
            '2fa' => EnsureTwoFactorSetup::class,
            'site.bySlug' => EnsureSiteBySlug::class,
            'can-manage-sites' => EnsureCanManageSites::class,
        ]);

        // Rozpoznanie sub-witryny po domenie (np. pokrzywdzeni.krafos.pl) musi
        // zadziałać zanim ktokolwiek dotknie SiteSetting::current(), dlatego
        // idzie przed wszystkim innym — także przed HandleRedirects. Jedno
        // wywołanie z jawną kolejnością, żeby dwa kolejne prepend() się nie
        // przestawiły względem siebie.
        $middleware->web(prepend: [
            ResolveSiteByDomain::class,
            HandleRedirects::class,
        ]);

        // Przełącznik aktywnej witryny w panelu admina (patrz
        // Admin\ActiveSiteController) — działa niezależnie od rozpoznawania
        // po domenie/ścieżce na froncie.
        $middleware->group('admin-site', [
            ResolveAdminActiveSite::class,
        ]);

        // EnsureSiteBySlug musi zadziałać PRZED SubstituteBindings — inaczej
        // niejawne wiązanie modelu ({news:slug} itp.) próbuje odpytać
        // News::forCurrentSite() zanim middleware zdąży podmienić bieżącą
        // witrynę, i zawsze trafia w witrynę główną zamiast sub-witryny z
        // adresu. SubstituteBindings jest częścią grupy „web” i domyślnie
        // wykonuje się przed middleware'em przypisanym wprost do trasy, więc
        // trzeba to jawnie wymusić priorytetem.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: EnsureSiteBySlug::class,
        );

        // Tryb konserwacji — dopięty na końcu grupy „web” (po starcie sesji, więc
        // rozpoznaje zalogowanych użytkowników panelu).
        $middleware->web(append: [
            EnsureSiteAvailable::class,
            MinifyHtmlResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Błędy HTTP (400, 403, 404, 419, 429, 500 itd.) w panelu administracyjnym
        // renderujemy w stylu strony frontowej (breadcrumbs + baner marki), zamiast
        // domyślnych widoków resources/views/errors/*.blade.php przeznaczonych dla
        // frontu bez kontekstu panelu.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $adminPrefix = config('app.admin_prefix', 'admin');

            if (! $request->is($adminPrefix, $adminPrefix.'/*')) {
                return null;
            }

            $isHttpException = $e instanceof HttpExceptionInterface;

            // Nieoczekiwane (nie-HTTP) wyjątki z APP_DEBUG=true zostawiamy debugerowi
            // (Ignition/Whoops) — tak jak przy standardowym zachowaniu frameworka.
            if (! $isHttpException && config('app.debug')) {
                return null;
            }

            $status = $isHttpException ? $e->getStatusCode() : 500;

            if ($status < 400) {
                return null;
            }

            try {
                return response()->view('errors.admin.default', [
                    'status' => $status,
                ], $status, $isHttpException ? $e->getHeaders() : []);
            } catch (\Throwable) {
                return null;
            }
        });
    })->create();
