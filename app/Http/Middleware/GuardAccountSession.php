<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\ImpersonationController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strażnik zalogowanej sesji (grupa „web”, rejestrowany w AppServiceProvider):
 * - konto poza okresem ważności (users.active_from / active_until) jest od razu wylogowywane z komunikatem —
 *   podczas przełączenia na cudze konto administrator wraca na swoje,
 * - podczas przełączenia na cudze konto (ImpersonationController) nie da się zmienić hasła, zabezpieczeń
 *   ani usunąć konta tej osoby.
 */
class GuardAccountSession
{
    /** Trasy zmieniające hasło i zabezpieczenia konta — zablokowane podczas przełączenia. */
    private const PROTECTED_ROUTES = ['profile.update', 'profile.destroy', 'password.update', 'password.confirm', 'two-factor.*', 'passkeys.*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $request->hasSession()) {
            return $next($request);
        }
        $session = $request->session();
        $impersonating = $session->has(ImpersonationController::SESSION_KEY);

        if (! $user->isAccountActive()) {
            $message = $user->accountWindowMessage();
            if ($impersonating && ($admin = ImpersonationController::restoreAdmin($request))) {
                return redirect()->route('admin.uzytkownicy.index')->with('error', "Konto „{$user->name}” jest poza okresem ważności — jesteś z powrotem na swoim koncie.");
            }
            activity('auth')->causedBy($user)->event('logout_expired')->withProperty('label', $user->name)->log('Wylogowanie: konto poza okresem ważności');
            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('login')->with('status', $message);
        }

        if ($impersonating && ! $request->isMethodSafe() && $request->routeIs(...self::PROTECTED_ROUTES)) {
            $message = 'Pracujesz na cudzym koncie — hasła ani zabezpieczeń tej osoby nie zmienisz. Wróć do swojego konta.';

            return $request->expectsJson() ? response()->json(['message' => $message], 403) : back()->with('error', $message);
        }

        return $next($request);
    }
}
