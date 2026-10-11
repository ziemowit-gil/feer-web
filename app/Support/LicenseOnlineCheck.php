<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sprawdzanie klucza licencji w panelu licencji weCMS (POST /api/v1/licenses/check).
 *
 * Podpis klucza weryfikuje się offline (LicenseValidator), ale tylko panel wie, że
 * klucz unieważniono albo zastąpiono nowym. Wynik ostatniego udanego sprawdzenia
 * leży w pliku (przetrwa cache:clear), a Edition bierze go pod uwagę.
 *
 * ─── DLACZEGO TAK ──────────────────────────────────────────────────────────
 * Panel może być chwilowo niedostępny — wtedy zostaje poprzedni znany wynik
 * i nic się nie zmienia. Wersję płatną odbiera tylko JEDNOZNACZNA odpowiedź
 * panelu (revoked, replaced, expired, domain_mismatch). „not_found” jest
 * ignorowane: tak samo wyglądałby panel z innym kluczem RSA albo pustą bazą,
 * a przez błąd konfiguracji panelu żaden klient nie może stracić wersji płatnej.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class LicenseOnlineCheck
{
    /** Powody, przy których panel odbiera wersję płatną. */
    public const BLOCKING_REASONS = ['revoked', 'replaced', 'expired', 'domain_mismatch'];

    public const REASON_LABELS = [
        'revoked' => 'licencja unieważniona w panelu',
        'replaced' => 'klucz zastąpiony nowym — wklej nowy LICENSE_KEY',
        'expired' => 'licencja wygasła',
        'domain_mismatch' => 'klucz wystawiono dla innej domeny',
        'not_found' => 'panel nie zna tego klucza (ignorowane)',
    ];

    public static function enabled(): bool
    {
        return filled(config('editions.check_url')) && filled(config('editions.license_key'));
    }

    /**
     * Pyta panel o klucz i zapisuje wynik. Zwraca zapisany wynik albo null, gdy
     * zapytanie się nie udało (wtedy poprzedni wynik zostaje bez zmian).
     *
     * @return array{valid: bool, reason: ?string, edition: ?string, expires_on: ?string, checked_at: string, key_hash: string}|null
     */
    public static function run(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('editions.check_timeout', 5))
                ->acceptJson()
                ->post((string) config('editions.check_url'), [
                    'key' => (string) config('editions.license_key'),
                    'domain' => (string) config('app.url'),
                ]);
        } catch (Throwable $e) {
            Log::warning('Sprawdzanie licencji online: brak połączenia z panelem.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful() || ! is_bool($response->json('valid'))) {
            Log::warning('Sprawdzanie licencji online: nieoczekiwana odpowiedź panelu.', ['status' => $response->status()]);

            return null;
        }

        $result = [
            'valid' => (bool) $response->json('valid'),
            'reason' => $response->json('reason'),
            'edition' => $response->json('edition'),
            'expires_on' => $response->json('expires_on'),
            'checked_at' => now()->toIso8601String(),
            'key_hash' => self::keyHash(),
        ];

        file_put_contents(self::path(), json_encode($result, JSON_PRETTY_PRINT));
        Edition::flush();

        return $result;
    }

    /** Ostatni zapisany wynik — tylko jeśli dotyczy obecnego klucza. */
    public static function last(): ?array
    {
        if (! is_file(self::path())) {
            return null;
        }

        $result = json_decode((string) file_get_contents(self::path()), true);

        return is_array($result) && ($result['key_hash'] ?? null) === self::keyHash() ? $result : null;
    }

    /** Czy ostatnia odpowiedź panelu odbiera wersję płatną. */
    public static function blocks(): bool
    {
        $last = self::last();

        return $last !== null && ! $last['valid'] && in_array($last['reason'], self::BLOCKING_REASONS, true);
    }

    public static function path(): string
    {
        return storage_path('app/license-check.json');
    }

    /** Zmiana LICENSE_KEY unieważnia zapisany wynik — nowy klucz nie dziedziczy blokady. */
    private static function keyHash(): string
    {
        return hash('sha256', strtoupper((string) preg_replace('/[\s-]+/', '', (string) config('editions.license_key'))));
    }
}
