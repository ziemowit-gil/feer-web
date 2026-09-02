<?php

namespace App\Support;

use RuntimeException;

/**
 * Certyfikat klienta (.pfx) używany do logowania głównego administratora pod /super.
 *
 * Certyfikat jest samopodpisany i generowany jednorazowo podczas instalacji —
 * serwer przechowuje wyłącznie odcisk (SHA-256) certyfikatu publicznego, nigdy
 * klucz prywatny. Sam plik .pfx (z kluczem prywatnym) trafia do wdrażającego
 * do jednorazowego pobrania i nie jest zapisywany na dysku serwera.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SuperAdminCertificate
{
    /**
     * Generuje samopodpisany certyfikat klienta i pakuje go do .pfx.
     *
     * @return array{pfx: string, fingerprint: string}
     */
    public static function generate(string $commonName, string $organization, string $passphrase): array
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privateKey === false) {
            throw new RuntimeException('Nie udało się wygenerować klucza prywatnego: '.openssl_error_string());
        }

        $csr = openssl_csr_new([
            'commonName' => $commonName,
            'organizationName' => $organization,
        ], $privateKey, ['digest_alg' => 'sha256']);

        if ($csr === false) {
            throw new RuntimeException('Nie udało się wygenerować CSR: '.openssl_error_string());
        }

        $cert = openssl_csr_sign($csr, null, $privateKey, 3650, ['digest_alg' => 'sha256']);

        if ($cert === false) {
            throw new RuntimeException('Nie udało się podpisać certyfikatu: '.openssl_error_string());
        }

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($privateKey, $keyPem);

        $exported = openssl_pkcs12_export($certPem, $pfxOut, $keyPem, $passphrase);

        if (! $exported) {
            throw new RuntimeException('Nie udało się spakować certyfikatu do .pfx: '.openssl_error_string());
        }

        $fingerprint = openssl_x509_fingerprint($certPem, 'sha256');

        if ($fingerprint === false) {
            throw new RuntimeException('Nie udało się obliczyć odcisku certyfikatu.');
        }

        return ['pfx' => $pfxOut, 'fingerprint' => $fingerprint];
    }

    /**
     * Odczytuje przesłany plik .pfx i zwraca odcisk (SHA-256) zawartego w nim
     * certyfikatu, albo null gdy plik/hasło jest nieprawidłowe.
     */
    public static function fingerprintFromUpload(string $binary, string $passphrase): ?string
    {
        if (! openssl_pkcs12_read($binary, $certs, $passphrase)) {
            return null;
        }

        $fingerprint = openssl_x509_fingerprint($certs['cert'] ?? '', 'sha256');

        return $fingerprint !== false ? $fingerprint : null;
    }
}
