<?php

namespace App\Support;

/**
 * Walidacja kluczy licencyjnych weCMS (RSA SHA-256).
 *
 * Klucz publiczny pochodzi z env LICENSE_PUBLIC_KEY.
 * Jeśli zmienna nie jest ustawiona, walidacja jest pomijana (tryb dev).
 */
class LicenseValidator
{
    public static function isDevMode(): bool
    {
        return empty(config('license.public_key'));
    }

    /**
     * Waliduje klucz licencyjny i zwraca payload lub null przy błędzie.
     *
     * @return array{d:string,e:?string,x:string,n:string,i:string}|null
     */
    public static function validate(string $licenseKey, ?string $domain = null): ?array
    {
        if (self::isDevMode()) {
            return ['d' => '*', 'e' => null, 'x' => 'dev', 'n' => 'dev-mode', 'i' => date('Y-m-d')];
        }

        // Usuń prefiks WCMS- i myślniki między blokami
        $cleaned = preg_replace('/^WCMS-/', '', strtoupper($licenseKey));
        $cleaned = str_replace('-', '', $cleaned);

        // Przywróć base64url (małe litery + znaki)
        // Format: base64url(payload) . "." . base64url(signature)
        // Ale enkodowaliśmy jako uppercase — trzeba zdekodować z lowercase
        // Generator zamienił na uppercase, więc przywracamy lowercase dla base64url
        $raw = strtolower($cleaned);

        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $sigB64] = $parts;

        $payload   = base64_decode(strtr($payloadB64, '-_', '+/'));
        $signature = base64_decode(strtr($sigB64, '-_', '+/'));

        if ($payload === false || $signature === false) {
            return null;
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return null;
        }

        // Weryfikacja podpisu RSA
        $pubKey = openssl_pkey_get_public(config('license.public_key'));
        if (! $pubKey) {
            return null;
        }

        $ok = openssl_verify($payload, $signature, $pubKey, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) {
            return null;
        }

        // Weryfikacja domeny
        if ($domain && $data['d'] !== '*') {
            $licensedHost  = strtolower(parse_url('https://' . $data['d'], PHP_URL_HOST) ?: $data['d']);
            $requestedHost = strtolower(parse_url($domain, PHP_URL_HOST) ?: $domain);
            if ($licensedHost !== $requestedHost) {
                return null;
            }
        }

        // Weryfikacja daty wygaśnięcia
        if (! empty($data['e']) && $data['e'] < date('Y-m-d')) {
            return null;
        }

        return $data;
    }

    public static function errorMessage(string $licenseKey, ?string $domain = null): ?string
    {
        if (self::isDevMode()) {
            return null;
        }

        if (empty($licenseKey)) {
            return 'Klucz licencyjny jest wymagany.';
        }

        $cleaned = preg_replace('/^WCMS-/', '', strtoupper($licenseKey));
        $raw     = strtolower(str_replace('-', '', $cleaned));
        $parts   = explode('.', $raw, 2);

        if (count($parts) !== 2) {
            return 'Nieprawidłowy format klucza licencyjnego.';
        }

        $payload = base64_decode(strtr($parts[0], '-_', '+/'));
        $sig     = base64_decode(strtr($parts[1], '-_', '+/'));
        $data    = $payload ? json_decode($payload, true) : null;

        if (! is_array($data)) {
            return 'Uszkodzony klucz licencyjny.';
        }

        $pubKey = openssl_pkey_get_public(config('license.public_key'));
        if ($pubKey && openssl_verify($payload, $sig, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            return 'Klucz licencyjny jest nieprawidłowy lub sfałszowany.';
        }

        if (! empty($data['e']) && $data['e'] < date('Y-m-d')) {
            return 'Klucz licencyjny wygasł dnia ' . $data['e'] . '.';
        }

        if ($domain && $data['d'] !== '*') {
            return 'Klucz nie obejmuje domeny ' . parse_url($domain, PHP_URL_HOST) . '.';
        }

        return 'Klucz licencyjny jest nieprawidłowy.';
    }
}
