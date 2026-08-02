<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor\TwoFactorService;
use App\Services\TwoFactor\YubikeyVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Drugi składnik logowania hasłem — pełne zalogowanie wymaga podania
 * kodu TOTP, kodu zapasowego lub jednorazowego hasła z klucza YubiKey.
 *
 * Metody: create(), store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class TwoFactorChallengeController extends Controller
{
    /** Wyświetla formularz weryfikacji drugiego składnika logowania (TOTP / YubiKey). */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'yubikeyAvailable' => (bool) $this->pendingUser($request)->hasYubikey(),
            'totpAvailable' => (bool) $this->pendingUser($request)->hasTotpEnabled(),
        ]);
    }

    /** Weryfikuje kod 2FA (TOTP, YubiKey lub kod zapasowy) i finalizuje logowanie. */
    public function store(Request $request, TwoFactorService $totp, YubikeyVerifier $yubikey): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'yubikey' => ['nullable', 'string'],
        ]);

        $passed = false;

        // 1) Klucz YubiKey (jeśli podano OTP z klucza).
        if (filled($request->input('yubikey')) && $user->hasYubikey()) {
            $otp = trim((string) $request->input('yubikey'));
            $passed = $yubikey->verify($otp)
                && in_array($yubikey->publicId($otp), (array) $user->yubikey_ids, true);
        }

        // 2) Kod TOTP lub kod zapasowy.
        if (! $passed && filled($request->input('code'))) {
            $code = trim((string) $request->input('code'));

            if ($user->hasTotpEnabled() && $totp->verify((string) $user->two_factor_secret, $code)) {
                $passed = true;
            } elseif ($this->consumeRecoveryCode($user, $code)) {
                $passed = true;
            }
        }

        if (! $passed) {
            throw ValidationException::withMessages([
                'code' => 'Nieprawidłowy kod uwierzytelniający. Spróbuj ponownie.',
            ]);
        }

        $remember = (bool) $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget('login.2fa.user_id');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('login_method', 'password');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Zużyj (usuń) pasujący kod zapasowy, jeśli istnieje. */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = (array) $user->two_factor_recovery_codes;

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_diff($codes, [$code])),
        ])->save();

        return true;
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get('login.2fa.user_id');

        return $id ? User::find($id) : null;
    }
}
