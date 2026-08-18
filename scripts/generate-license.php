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
 *   --edition   standard | premium | municipality (domyślnie: standard)
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

if (! in_array($edition, ['standard', 'premium', 'municipality'], true)) {
    echo "Błąd: --edition musi być: standard, premium, municipality\n";
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
// Format: base64url(payload) . "." . base64url(signature)
// Sformatowane jako WCMS-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$raw = base64url_encode($payload) . '.' . base64url_encode($signature);

// Dodaj prefiks i poformattuj w bloki po 8 znaków
$body    = strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $raw));
$license = 'WCMS-' . implode('-', str_split($body, 8));

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
