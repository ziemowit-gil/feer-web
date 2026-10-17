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
 * Logowanie administratorów i redaktorów do panelu przez Microsoft 365 (Socialite / SSO).
 *
 * Metody: redirect(), callback().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class MicrosoftAuthController extends Controller
{
    /**
     * Przekieruj użytkownika do logowania Microsoft 365.
     */
    public function redirect(): RedirectResponse
    {
        if (! SiteSetting::current()->microsoftLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie przez Microsoft 365 jest obecnie wyłączone.');
        }

        $this->applyMicrosoftConfig();

        return Socialite::driver('microsoft')->redirect();
    }

    /** Klucz sesji: kto potwierdza tożsamość przez Microsoft 365 zamiast hasła (strona „Potwierdź hasło”). */
    public const CONFIRM_SESSION_KEY = 'microsoft_confirm_user_id';

    /**
     * Potwierdzenie tożsamości zalogowanej osoby przez Microsoft 365 (zamiast hasła) przed wrażliwą operacją.
     * Microsoft wymusza ponowne zalogowanie (prompt=login); wynik obsługuje ten sam callback co logowanie.
     */
    public function confirm(): RedirectResponse
    {
        if (! SiteSetting::current()->microsoftLoginEnabled()) {
            return redirect()->route('password.confirm')->with('error', 'Logowanie przez Microsoft 365 jest obecnie wyłączone.');
        }

        request()->session()->put(self::CONFIRM_SESSION_KEY, Auth::id());
        $this->applyMicrosoftConfig();

        return Socialite::driver('microsoft')->with(['prompt' => 'login'])->redirect();
    }

    /**
     * Obsłuż powrót z Microsoft 365 i zaloguj użytkownika do panelu — albo, w trybie potwierdzenia,
     * potwierdź tożsamość zalogowanej osoby (konto Microsoft musi należeć do niej).
     */
    public function callback(): RedirectResponse
    {
        $confirmFor = request()->session()->pull(self::CONFIRM_SESSION_KEY);

        // Zalogowana osoba poza trybem potwierdzenia — jak wcześniej middleware „guest”.
        if (Auth::check() && ! $confirmFor) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if (! SiteSetting::current()->microsoftLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie przez Microsoft 365 jest obecnie wyłączone.');
        }

        $this->applyMicrosoftConfig();

        if ($confirmFor && Auth::check()) {
            return $this->confirmCallback((int) $confirmFor);
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Throwable $e) {
            Log::warning('Logowanie Microsoft 365 nie powiodło się.', ['exception' => $e->getMessage()]);

            return redirect()->route('login')->with('error', 'Logowanie przez Microsoft 365 nie powiodło się. Spróbuj ponownie.');
        }

        // Dostęp mają wyłącznie konta wcześniej utworzone w panelu (dopasowanie po Microsoft ID lub e-mailu).
        // Dzięki temu żadne przypadkowe konto Microsoft nie uzyska dostępu do panelu.
        $user = User::where('microsoft_id', $microsoftUser->getId())->first()
            ?? User::where('email', $microsoftUser->getEmail())->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'To konto Microsoft 365 nie ma dostępu do panelu. Skontaktuj się z administratorem.');
        }

        // Powiąż konto z Microsoft ID i zapisz podstawowe dane przy pierwszym logowaniu SSO.
        $user->forceFill([
            'microsoft_id' => $microsoftUser->getId(),
            'avatar' => $microsoftUser->getAvatar() ?: $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, remember: true);

        request()->session()->regenerate();
        // MS365 ma własne MFA — logowanie przez SSO pomija nasze 2FA.
        request()->session()->put('login_method', 'microsoft');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Tryb potwierdzenia: konto Microsoft (ID albo e-mail) musi należeć do zalogowanej osoby. */
    private function confirmCallback(int $userId): RedirectResponse
    {
        $user = Auth::user();
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Throwable $e) {
            Log::warning('Potwierdzenie przez Microsoft 365 nie powiodło się.', ['exception' => $e->getMessage()]);

            return redirect()->route('password.confirm')->with('error', 'Potwierdzenie przez Microsoft 365 nie powiodło się. Spróbuj ponownie.');
        }

        $matches = $user->id === $userId && (
            ($user->microsoft_id && hash_equals((string) $user->microsoft_id, (string) $microsoftUser->getId()))
            || (! $user->microsoft_id && $microsoftUser->getEmail() && mb_strtolower($microsoftUser->getEmail()) === mb_strtolower((string) $user->email))
        );
        if (! $matches) {
            return redirect()->route('password.confirm')->with('error', 'To konto Microsoft 365 nie należy do zalogowanej osoby — zaloguj się w Microsoft na swoje konto.');
        }

        request()->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Wstrzyknij konfigurację MS365 z panelu do Socialite (nadpisuje .env).
     */
    private function applyMicrosoftConfig(): void
    {
        config(['services.microsoft' => SiteSetting::current()->microsoftConfig()]);
    }
}
