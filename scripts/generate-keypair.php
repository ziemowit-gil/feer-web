#!/usr/bin/env php
<?php

/**
 * Jednorazowy generator pary kluczy RSA dla systemu licencji weCMS.
 *
 * Uruchom RAZ, zachowaj private.pem w bezpiecznym miejscu (poza repo!),
 * a public.pem skopiuj do config/license.php lub wklej do .env jako
 * LICENSE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----".
 *
 * Użycie:
 *   php scripts/generate-keypair.php
 */

if (PHP_SAPI !== 'cli') {
    echo "Uruchom przez CLI.\n";
    exit(1);
}

$config = [
    'digest_alg'       => 'sha256',
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

echo "Generuję parę kluczy RSA 2048-bit...\n";
$res = openssl_pkey_new($config);
if (!$res) {
    echo "Błąd: " . openssl_error_string() . "\n";
    exit(1);
}

openssl_pkey_export($res, $privateKey);
$details   = openssl_pkey_get_details($res);
$publicKey = $details['key'];

$dir = __DIR__;

file_put_contents("$dir/private.pem", $privateKey);
file_put_contents("$dir/public.pem", $publicKey);

chmod("$dir/private.pem", 0600);

echo "\n";
echo "✔ Klucze zapisane:\n";
echo "   private.pem — klucz prywatny (TYLKO dla właściciela, poza repo!)\n";
echo "   public.pem  — klucz publiczny (wklej do .env lub config/license.php)\n";
echo "\n";
echo "Klucz publiczny do wklejenia w .env:\n";
echo "LICENSE_PUBLIC_KEY=\"" . str_replace("\n", "\\n", trim($publicKey)) . "\"\n";
echo "\n";
echo "UWAGA: dodaj scripts/private.pem do .gitignore!\n";
