<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // Jeśli konto ma włączone 2FA — wyloguj i wymagaj drugiego składnika
        // przed pełnym zalogowaniem (dane oczekujące trzymamy w sesji).
        if ($user->hasTwoFactorEnabled()) {
            $remember = $request->boolean('remember');
            Auth::guard('web')->logout();

            $request->session()->put('login.2fa.user_id', $user->id);
            $request->session()->put('login.2fa.remember', $remember);

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();
        $request->session()->put('login_method', 'password');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
