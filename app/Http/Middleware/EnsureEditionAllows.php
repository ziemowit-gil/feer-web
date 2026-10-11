<?php

namespace App\Http\Middleware;

use App\Support\Edition;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bramka funkcji płatnej: `->middleware('edition:multisite')`.
 *
 * W wersji darmowej funkcja działa w trybie tylko do odczytu — żądania GET/HEAD
 * przechodzą (w panelu z informacją nad treścią), a zapisy i akcje są
 * odrzucane z komunikatem. Dzięki temu po wygaśnięciu licencji nic nie znika.
 */
class EnsureEditionAllows
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (Edition::allows($feature)) {
            return $next($request);
        }

        return self::readOnly($request, $next, $feature);
    }

    /** Wspólne z EnsureModuleEnabled (moduły płatne). */
    public static function readOnly(Request $request, Closure $next, string $feature): Response
    {
        $message = Edition::lockedMessage($feature);

        if ($request->isMethodSafe()) {
            if ($request->routeIs('admin.*')) {
                View::share('editionLocked', $message);
            }

            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 402);
        }

        return $request->routeIs('admin.*')
            ? back()->with('error', $message)
            : back()->withInput()->withErrors(['edition' => $message]);
    }
}
