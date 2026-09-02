<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dostęp do zarządzania witrynami sieci (Admin\SiteController,
 * Admin\ActiveSiteController) — zwykli administratorzy (rola „admin") plus
 * jawna, zakodowana na sztywno furtka awaryjna: konto serwis@local ma to
 * uprawnienie zawsze, niezależnie od przypisanej roli. To świadomy wyjątek
 * (na życzenie — "na wypadek W"), nie ogólny wzorzec do naśladowania gdzie
 * indziej; nie rozszerzać na inne ekrany bez wyraźnej decyzji.
 */
class EnsureCanManageSites
{
    private const EMERGENCY_ACCESS_EMAIL = 'serwis@local';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->isAdmin() || $user?->email === self::EMERGENCY_ACCESS_EMAIL, 403);

        return $next($request);
    }
}
