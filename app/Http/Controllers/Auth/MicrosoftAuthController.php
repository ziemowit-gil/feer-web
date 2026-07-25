<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

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

    /**
     * Obsłuż powrót z Microsoft 365 i zaloguj użytkownika do panelu.
     */
    public function callback(): RedirectResponse
    {
        if (! SiteSetting::current()->microsoftLoginEnabled()) {
            return redirect()->route('login')->with('error', 'Logowanie przez Microsoft 365 jest obecnie wyłączone.');
        }

        $this->applyMicrosoftConfig();

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
