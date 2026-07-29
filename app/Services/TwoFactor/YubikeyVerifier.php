<?php

namespace App\Services\TwoFactor;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Weryfikacja jednorazowych haseł YubiKey (Yubico OTP) przez usługę Yubico
 * Web Service v2.0. Implementacja natywna (bez zewnętrznej paczki) — podpisuje
 * żądanie HMAC-SHA1 i sprawdza podpis odpowiedzi, zgodnie z protokołem Yubico.
 *
 * @see https://developers.yubico.com/OTP/Specifications/OTP_validation_protocol.html
 */
class YubikeyVerifier
{
    private const ENDPOINT = 'https://api.yubico.com/wsapi/2.0/verify';

    /** Publiczny identyfikator klucza = pierwsze 12 znaków OTP (stała część). */
    public function publicId(string $otp): string
    {
        return substr(trim($otp), 0, 12);
    }

    /** Czy OTP wygląda na poprawny Yubico OTP (44 znaki modhex). */
    public function looksLikeOtp(string $otp): bool
    {
        $otp = trim($otp);

        return strlen($otp) === 44 && preg_match('/^[cbdefghijklnrtuv]+$/', $otp) === 1;
    }

    /**
     * Zweryfikuj OTP w usłudze Yubico. Zwraca true tylko gdy status = OK oraz
     * podpis, nonce i OTP w odpowiedzi się zgadzają (ochrona przed podmianą).
     */
    public function verify(string $otp): bool
    {
        $otp = trim($otp);
        $settings = SiteSetting::current();

        if (! $settings->yubicoConfigured() || ! $this->looksLikeOtp($otp)) {
            return false;
        }

        $clientId = (string) $settings->yubico_client_id;
        $secret = base64_decode((string) $settings->yubico_secret_key, true);

        if ($secret === false) {
            return false;
        }

        $nonce = strtolower(Str::random(32));
        $params = [
            'id' => $clientId,
            'nonce' => $nonce,
            'otp' => $otp,
        ];

        $params['h'] = $this->sign($params, $secret);

        try {
            $response = Http::timeout(10)->get(self::ENDPOINT, $params);
        } catch (\Throwable $e) {
            Log::warning('Weryfikacja YubiKey nie powiodła się (połączenie).', ['exception' => $e->getMessage()]);

            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        $data = $this->parseResponse($response->body());

        if (($data['status'] ?? null) !== 'OK') {
            return false;
        }

        // Odpowiedź musi dotyczyć tego samego OTP i nonce, oraz mieć poprawny podpis.
        if (($data['otp'] ?? null) !== $otp || ($data['nonce'] ?? null) !== $nonce) {
            return false;
        }

        return $this->verifyResponseSignature($data, $secret);
    }

    /**
     * @param  array<string, string>  $params
     */
    private function sign(array $params, string $secret): string
    {
        ksort($params);
        $query = urldecode(http_build_query($params));

        return base64_encode(hash_hmac('sha1', $query, $secret, true));
    }

    /**
     * @param  array<string, string>  $data
     */
    private function verifyResponseSignature(array $data, string $secret): bool
    {
        $signature = $data['h'] ?? null;

        if (! is_string($signature)) {
            return false;
        }

        $signed = $data;
        unset($signed['h']);

        return hash_equals($this->sign($signed, $secret), $signature);
    }

    /**
     * Rozbij odpowiedź „key=value" (po jednej parze w linii) na tablicę.
     *
     * @return array<string, string>
     */
    private function parseResponse(string $body): array
    {
        $result = [];

        foreach (preg_split('/\r\n|\r|\n/', trim($body)) as $line) {
            if ($line === '' || ! str_contains($line, '=')) {
                continue;
            }

            // Wartości (np. podpis base64) mogą zawierać „=", więc dzielimy tylko raz.
            [$key, $value] = explode('=', $line, 2);
            $result[trim($key)] = trim($value);
        }

        return $result;
    }
}
