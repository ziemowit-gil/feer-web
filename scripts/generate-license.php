#!/usr/bin/env php
<?php

/**
 * Generator kluczy licencyjnych weCMS.
 *
 * Wymaga scripts/private.pem (wygenerowany przez generate-keypair.php).
 *
 * Użycie:
 *   php scripts/generate-license.php --domain=klient.pl --expires=2027-12-31 --edition=standard
 *   php scripts/generate-license.php --domain=* --expires=2099-12-31 --edition=premium
 *
 * Opcje:
 *   --domain    Domena klienta lub * (dla środowisk dev/demo)
 *   --expires   Data wygaśnięcia YYYY-MM-DD (domyślnie: bez limitu)
 *   --edition   free | standard | premium | municipality (domyślnie: standard)
 *
 * Klucze zwykle wystawia panel licencji (osobna aplikacja) — ten skrypt to zapas
 * na wypadek pracy bez panelu. Format klucza: patrz App\Support\LicenseValidator.
 *   --note      Opcjonalna notatka (np. nazwa klienta)
 */

if (PHP_SAPI !== 'cli') {
    echo "Uruchom przez CLI.\n";
    exit(1);
}

// ── Parsowanie argumentów ─────────────────────────────────────────────────────

$opts = getopt('', ['domain:', 'expires:', 'edition:', 'note:']);

$domain  = $opts['domain']  ?? null;
$expires = $opts['expires'] ?? null;
$edition = $opts['edition'] ?? 'standard';
$note    = $opts['note']    ?? '';

if (! $domain) {
    echo "Błąd: wymagany parametr --domain\n";
    echo "Przykład: php scripts/generate-license.php --domain=klient.pl --expires=2027-12-31\n";
    exit(1);
}

if (! in_array($edition, ['free', 'standard', 'premium', 'municipality'], true)) {
    echo "Błąd: --edition musi być: free, standard, premium, municipality\n";
    exit(1);
}

if ($expires && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) {
    echo "Błąd: --expires musi być w formacie YYYY-MM-DD\n";
    exit(1);
}

// ── Klucz prywatny ────────────────────────────────────────────────────────────

$privateKeyPath = __DIR__ . '/private.pem';
if (! file_exists($privateKeyPath)) {
    echo "Błąd: nie znaleziono scripts/private.pem\n";
    echo "Najpierw uruchom: php scripts/generate-keypair.php\n";
    exit(1);
}

$privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
if (! $privateKey) {
    echo "Błąd: nie można załadować klucza prywatnego\n";
    exit(1);
}

// ── Payload ───────────────────────────────────────────────────────────────────

$payload = json_encode([
    'd' => $domain,
    'e' => $expires ?: null,
    'x' => $edition,
    'n' => $note,
    'i' => date('Y-m-d'),        // data wystawienia
], JSON_UNESCAPED_UNICODE);

// ── Podpis ────────────────────────────────────────────────────────────────────

openssl_sign($payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

// ── Kodowanie klucza ─────────────────────────────────────────────────────────
// Format: WCMS-<base32(payload)>.<base32(podpis)>, oba w blokach po 8 znaków.
// Base32 nie rozróżnia wielkości liter — patrz App\Support\LicenseValidator.

function base32_encode_raw(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($data) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    }

    return $out;
}

$license = 'WCMS-'
    . implode('-', str_split(base32_encode_raw($payload), 8))
    . '.'
    . implode('-', str_split(base32_encode_raw($signature), 8));

// ── Output ────────────────────────────────────────────────────────────────────

$expiresDisplay = $expires ?: 'bezterminowo';

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║         KLUCZ LICENCYJNY weCMS                       ║\n";
echo "╠══════════════════════════════════════════════════════╣\n";
printf("║  Domena:   %-41s║\n", $domain);
printf("║  Edycja:   %-41s║\n", $edition);
printf("║  Ważny do: %-41s║\n", $expiresDisplay);
if ($note) {
    printf("║  Notatka:  %-41s║\n", mb_substr($note, 0, 41));
}
echo "╠══════════════════════════════════════════════════════╣\n";
echo "║  Klucz:                                              ║\n";

// Wyświetl klucz w liniach po 54 znaki
$keyLines = str_split($license, 54);
foreach ($keyLines as $line) {
    printf("║  %-52s║\n", $line);
}

echo "╚══════════════════════════════════════════════════════╝\n";
echo "\n";

// Wersja do wklejenia
echo "Do skopiowania:\n";
echo $license . "\n\n";

// Opcjonalnie: zapisz do pliku
$outFile = __DIR__ . "/license_{$domain}_" . date('Ymd') . ".txt";
file_put_contents($outFile, implode("\n", [
    "weCMS License Key",
    "Generated: " . date('c'),
    "Domain: $domain",
    "Edition: $edition",
    "Expires: $expiresDisplay",
    "Note: $note",
    "",
    $license,
    "",
]));
echo "Zapisano do: $outFile\n";
