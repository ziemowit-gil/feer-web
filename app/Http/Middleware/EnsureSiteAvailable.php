<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Globalny tryb konserwacji (przerwa techniczna). Gdy jest włączony:
 *  - zwykli (niezalogowani) użytkownicy widzą komunikat o przerwie (HTTP 503),
 *  - zalogowani użytkownicy panelu pracują normalnie (mogą go wyłączyć),
 *  - logowanie hasłem pozostaje dostępne, aby admin mógł się zalogować,
 *  - logowanie przez Microsoft 365 jest zablokowane (patrz
 *    SiteSetting::microsoftLoginEnabled(), które w tym trybie zwraca false).
 */
class EnsureSiteAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        $settings = SiteSetting::current();

        if (! $settings->maintenance_mode) {
            return $next($request);
        }

        // Zalogowani użytkownicy panelu pracują normalnie.
        if ($request->user()) {
            return $next($request);
        }

        // Wyjścia awaryjne: logowanie hasłem, wylogowanie, healthcheck — żeby
        // administrator zawsze mógł wejść do panelu i wyłączyć tryb konserwacji.
        // Formularz logowania wysyłany jest przez POST /login, którego trasa nie
        // ma nazwy, więc dopuszczamy ścieżkę „login” wprost (routeIs('login')
        // złapałoby tylko GET), inaczej samo zalogowanie kończyłoby się 503.
        if ($request->routeIs('logout', 'password.*') || $request->is('login', 'up')) {
            return $next($request);
        }

        return response()->view('errors.maintenance', [
            'settings' => $settings,
            'message' => $settings->maintenanceMessage(),
        ], 503);
    }
}
