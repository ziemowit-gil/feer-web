<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Logowanie hasłem (e-mail + hasło) z obsługą 2FA (TOTP / YubiKey) i awaryjnego dostępu.
 *
 * Metody: create(), createEmergency(), store(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class AuthenticatedSessionController extends Controller
{
    /** Wyświetla formularz logowania hasłem. */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Formularz awaryjny pod losowym URL — pokazuje lokalny login bez względu
     * na tryb "tylko MS". Token walidowany względem ustawień; 404 gdy nie pasuje.
     */
    public function createEmergency(string $token): View
    {
        $stored = SiteSetting::current()->emergency_login_token;

        abort_if(! $stored || ! hash_equals($stored, $token), 404);

        return view('auth.login', ['emergency' => true]);
    }

    /** Uwierzytelnia użytkownika; przy włączonym 2FA przekierowuje do wyzwania drugiego składnika. */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Tryb "tylko MS": sprawdzamy flagę PRZED authenticate(), żeby nie
        // ujawniać przez różnicę błędów, czy hasło jest poprawne dla kont
        // bez uprawnień awaryjnych. Obie gałęzie zwracają ten sam auth.failed.
        if (SiteSetting::current()->microsoftOnlyLogin()) {
            $candidate = User::where('email', $request->string('email')->lower())->first();

            if (! $candidate?->local_login_allowed) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }
        }

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

    /** Wylogowuje użytkownika i inwaliduje sesję. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
