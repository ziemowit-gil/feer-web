<?php

return [
    /*
     * Klucz publiczny RSA do weryfikacji licencji weCMS.
     * Wygeneruj parę kluczy: php scripts/generate-keypair.php
     * Wklej klucz publiczny do .env jako LICENSE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
     *
     * Jeśli pusta — walidacja pominięta (tryb developerski).
     */
    'public_key' => env('LICENSE_PUBLIC_KEY')
        ? str_replace('\n', "\n", env('LICENSE_PUBLIC_KEY'))
        : null,
];
