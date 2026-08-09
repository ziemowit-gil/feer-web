<?php

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureSiteAvailable;
use App\Http\Middleware\EnsureTwoFactorSetup;
use App\Http\Middleware\EnsureUserCanAccessModule;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleRedirects;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        ]);

        // Ręczne przekierowania 301 — na początku grupy „web”, by zadziałały
        // zanim routing zwróci 404.
        $middleware->web(prepend: [
            HandleRedirects::class,
        ]);

        // Tryb konserwacji — dopięty na końcu grupy „web” (po starcie sesji, więc
        // rozpoznaje zalogowanych użytkowników panelu).
        $middleware->web(append: [
            EnsureSiteAvailable::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
