<?php

namespace App\Services\TwoFactor;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;

/**
 * Obsługa TOTP (Google Authenticator / kompatybilne): generowanie sekretu,
 * kodu QR, weryfikacja kodów oraz kodów zapasowych. Oparte na pragmarx/google2fa.
 */
class TwoFactorService
{
    public function __construct(private readonly Google2FA $engine) {}

    /** Nowy losowy sekret TOTP. */
    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /** Kod QR (data URI, SVG) do zeskanowania w aplikacji uwierzytelniającej. */
    public function qrCodeDataUri(User $user, string $secret): string
    {
        return $this->engine->getQRCodeInline(
            SiteSetting::current()->site_name ?: config('app.name'),
            $user->email,
            $secret,
        );
    }

    /** Zweryfikuj 6-cyfrowy kod TOTP względem sekretu (z tolerancją okna). */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        return $this->engine->verifyKey($secret, $code) !== false;
    }

    /**
     * Wygeneruj zestaw jednorazowych kodów zapasowych.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }
}
