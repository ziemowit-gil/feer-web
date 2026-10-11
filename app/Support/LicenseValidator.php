<?php

namespace App\Support;

/**
 * Walidacja kluczy licencyjnych weCMS (RSA SHA-256).
 *
 * Klucz publiczny pochodzi z env LICENSE_PUBLIC_KEY.
 * Jeśli zmienna nie jest ustawiona, walidacja jest pomijana (tryb dev).
 *
 * Format klucza (wystawia go panel licencji albo scripts/generate-license.php):
 *
 *   WCMS-<base32(payload) w blokach po 8>.<base32(podpis RSA) w blokach po 8>
 *
 * Base32 (RFC 4648, bez dopełnienia) nie rozróżnia wielkości liter, więc klucz
 * przepisany ręcznie, wklejony małymi literami czy z dodatkowymi spacjami/łamaniem
 * wierszy nadal się weryfikuje. Payload to JSON:
 *   d — domena (albo * dla dowolnej), e — data wygaśnięcia YYYY-MM-DD lub null,
 *   x — edycja (free | standard | premium | municipality), n — notatka, i — data wystawienia.
 */
class LicenseValidator
{
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

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

        $data = self::verifiedPayload($licenseKey);
        if ($data === null) {
            return null;
        }

        if ($domain && ! self::domainMatches((string) ($data['d'] ?? ''), $domain)) {
            return null;
        }

        if (! empty($data['e']) && $data['e'] < date('Y-m-d')) {
            return null;
        }

        return $data;
    }

    /** Powód odrzucenia klucza do pokazania w instalatorze; null = klucz w porządku. */
    public static function errorMessage(string $licenseKey, ?string $domain = null): ?string
    {
        if (self::isDevMode() || self::validate($licenseKey, $domain) !== null) {
            return null;
        }

        if (trim($licenseKey) === '') {
            return 'Klucz licencyjny jest wymagany.';
        }

        $decoded = self::decode($licenseKey);
        if ($decoded === null) {
            return 'Nieprawidłowy format klucza licencyjnego.';
        }

        $data = self::verifiedPayload($licenseKey);
        if ($data === null) {
            return 'Klucz licencyjny jest nieprawidłowy lub sfałszowany.';
        }

        if (! empty($data['e']) && $data['e'] < date('Y-m-d')) {
            return 'Klucz licencyjny wygasł dnia '.$data['e'].'.';
        }

        if ($domain && ! self::domainMatches((string) ($data['d'] ?? ''), $domain)) {
            return 'Klucz nie obejmuje domeny '.(parse_url($domain, PHP_URL_HOST) ?: $domain).'.';
        }

        return 'Klucz licencyjny jest nieprawidłowy.';
    }

    /** Payload z poprawnym podpisem (bez sprawdzania domeny i terminu) albo null. */
    private static function verifiedPayload(string $licenseKey): ?array
    {
        $decoded = self::decode($licenseKey);
        if ($decoded === null) {
            return null;
        }

        [$payload, $signature] = $decoded;

        $pubKey = openssl_pkey_get_public((string) config('license.public_key'));
        if (! $pubKey || openssl_verify($payload, $signature, $pubKey, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : null;
    }

    /** @return array{0: string, 1: string}|null [payload JSON, podpis] */
    private static function decode(string $licenseKey): ?array
    {
        $body = strtoupper((string) preg_replace('/\s+/', '', $licenseKey));
        $body = str_replace('-', '', (string) preg_replace('/^WCMS-?/', '', $body));
        $parts = explode('.', $body);

        if (count($parts) !== 2) {
            return null;
        }

        $payload = self::base32Decode($parts[0]);
        $signature = self::base32Decode($parts[1]);

        return $payload !== null && $signature !== null && $payload !== '' && $signature !== ''
            ? [$payload, $signature]
            : null;
    }

    private static function base32Decode(string $input): ?string
    {
        $bits = '';
        foreach (str_split($input) as $char) {
            $pos = strpos(self::BASE32, $char);
            if ($pos === false) {
                return null;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    /** Domena z licencji vs adres instalacji; „www.” po obu stronach nie ma znaczenia. */
    private static function domainMatches(string $licensed, string $requested): bool
    {
        if ($licensed === '*') {
            return true;
        }

        $normalize = function (string $value): string {
            $host = parse_url(str_contains($value, '://') ? $value : 'https://'.$value, PHP_URL_HOST) ?: $value;

            return (string) preg_replace('/^www\./', '', strtolower($host));
        };

        return $normalize($licensed) === $normalize($requested);
    }
}
