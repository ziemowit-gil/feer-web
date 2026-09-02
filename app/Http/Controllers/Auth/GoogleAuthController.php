<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Logowanie administratorów i redaktorów do panelu przez Google (Socialite) —
 * alternatywa dla logowania hasłem/Microsoft 365.
 *
 * Metody: redirect(), callback().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class GoogleAuthController extends Controller
{
    /** Przekieruj użytkownika do logowania Google. */
    public function redirect(): RedirectResponse
    {
        if (! SiteSetting::current()->googleLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie przez Google jest obecnie wyłączone.');
        }

        $this->applyGoogleConfig();

        return Socialite::driver('google')->redirect();
    }

    /** Obsłuż powrót z Google i zaloguj użytkownika do panelu. */
    public function callback(): RedirectResponse
    {
        if (! SiteSetting::current()->googleLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie przez Google jest obecnie wyłączone.');
        }

        $this->applyGoogleConfig();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Logowanie Google nie powiodło się.', ['exception' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Logowanie przez Google nie powiodło się. Spróbuj ponownie.');
        }

        // Dostęp mają wyłącznie konta wcześniej utworzone w panelu (dopasowanie po Google ID lub e-mailu).
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'To konto Google nie ma dostępu do panelu. Skontaktuj się z administratorem.');
        }

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar() ?: $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);

        request()->session()->regenerate();
        // Google ma własne MFA — logowanie przez SSO pomija nasze 2FA.
        request()->session()->put('login_method', 'google');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Wstrzyknij konfigurację Google z panelu do Socialite (nadpisuje .env). */
    private function applyGoogleConfig(): void
    {
        config(['services.google' => SiteSetting::current()->googleConfig()]);
    }
}
