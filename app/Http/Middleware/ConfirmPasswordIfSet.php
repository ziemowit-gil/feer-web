<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jak „password.confirm”, ale tylko dla kont z hasłem — konta logujące się wyłącznie przez SSO
 * (Microsoft, Google) nie mają czego potwierdzić i przeszłyby inaczej w ślepy zaułek.
 */
class ConfirmPasswordIfSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank($request->user()?->getAuthPassword())) {
            return $next($request);
        }

        return app(RequirePassword::class)->handle($request, $next);
    }
}
