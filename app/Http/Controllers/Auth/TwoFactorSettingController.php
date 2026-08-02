<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\TwoFactor\TwoFactorService;
use App\Services\TwoFactor\YubikeyVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Zarządzanie 2FA przez zalogowanego użytkownika panelu (na stronie profilu):
 * włączenie/potwierdzenie TOTP, kody zapasowe, rejestracja i usuwanie YubiKey.
 *
 * Metody: enable(), confirm(), disable(), regenerateRecovery(), addYubikey(), removeYubikey().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class TwoFactorSettingController extends Controller
{
    /** Rozpocznij konfigurację TOTP — wygeneruj sekret (jeszcze niepotwierdzony). */
    public function enable(Request $request, TwoFactorService $totp): RedirectResponse
    {
        $user = $request->user();

        // Nie nadpisuj już potwierdzonej konfiguracji.
        if (! $user->hasTotpEnabled()) {
            $user->forceFill([
                'two_factor_secret' => $totp->generateSecret(),
                'two_factor_confirmed_at' => null,
            ])->save();
        }

        return redirect()->route('profile.edit')->withFragment('dwuetapowe');
    }

    /** Potwierdź TOTP kodem z aplikacji — aktywuje 2FA i tworzy kody zapasowe. */
    public function confirm(Request $request, TwoFactorService $totp): RedirectResponse
    {
        $user = $request->user();

        $request->validate(['code' => ['required', 'string']]);

        if (blank($user->two_factor_secret) || ! $totp->verify((string) $user->two_factor_secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Nieprawidłowy kod. Upewnij się, że zegar urządzenia jest zsynchronizowany.',
            ]);
        }

        $codes = $totp->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $codes,
        ])->save();

        return redirect()->route('profile.edit')->withFragment('dwuetapowe')
            ->with('recovery_codes', $codes)
            ->with('status', 'two-factor-enabled');
    }

    /** Wyłącz TOTP (usuwa sekret i kody zapasowe). */
    public function disable(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->route('profile.edit')->withFragment('dwuetapowe')
            ->with('status', 'two-factor-disabled');
    }

    /** Wygeneruj nowy zestaw kodów zapasowych. */
    public function regenerateRecovery(Request $request, TwoFactorService $totp): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasTotpEnabled(), 400);

        $codes = $totp->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->route('profile.edit')->withFragment('dwuetapowe')
            ->with('recovery_codes', $codes);
    }

    /** Zarejestruj klucz YubiKey (weryfikacja OTP w usłudze Yubico). */
    public function addYubikey(Request $request, YubikeyVerifier $yubikey): RedirectResponse
    {
        $request->validate(['otp' => ['required', 'string']]);

        if (! SiteSetting::current()->yubicoConfigured()) {
            throw ValidationException::withMessages([
                'otp' => 'Uwierzytelnianie YubiKey nie jest skonfigurowane (Ustawienia → Logowanie).',
            ]);
        }

        $otp = trim((string) $request->input('otp'));

        if (! $yubikey->verify($otp)) {
            throw ValidationException::withMessages([
                'otp' => 'Nie udało się zweryfikować klucza YubiKey. Dotknij klucz w polu i spróbuj ponownie.',
            ]);
        }

        $user = $request->user();
        $ids = collect((array) $user->yubikey_ids)->push($yubikey->publicId($otp))->unique()->values()->all();

        $user->forceFill(['yubikey_ids' => $ids])->save();

        return redirect()->route('profile.edit')->withFragment('dwuetapowe')
            ->with('status', 'yubikey-added');
    }

    /** Usuń zarejestrowany klucz YubiKey. */
    public function removeYubikey(Request $request): RedirectResponse
    {
        $request->validate(['public_id' => ['required', 'string']]);

        $user = $request->user();
        $ids = array_values(array_diff((array) $user->yubikey_ids, [$request->input('public_id')]));

        $user->forceFill(['yubikey_ids' => $ids ?: null])->save();

        return redirect()->route('profile.edit')->withFragment('dwuetapowe')
            ->with('status', 'yubikey-removed');
    }
}
