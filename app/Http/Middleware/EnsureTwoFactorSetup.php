<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wymusza konfigurację 2FA dla administratorów logujących się hasłem. Konta
 * zalogowane przez Microsoft 365 są pomijane (MS ma własne MFA). Przekierowanie
 * prowadzi do profilu, który jest poza tym middleware — brak pętli.
 */
class EnsureTwoFactorSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->isAdmin()
            && $request->session()->get('login_method') === 'password'
            && SiteSetting::current()->two_factor_required_admins
            && ! $user->hasTwoFactorEnabled()
            && ! $user->isDemoAccount()
        ) {
            return redirect()->route('profile.edit')->withFragment('dwuetapowe')
                ->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
