<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Edycja tej instalacji weCMS: darmowa (free) albo płatna (pro).
 * Lista funkcji płatnych i hosty z pełną wersją — config/editions.php.
 *
 * Kolejność rozstrzygania:
 *  1. host z APP_URL z listy always_full_hosts (feer.org.pl i subdomeny) → pro, zawsze;
 *  2. wymuszenie EDITION=free|pro;
 *  3. środowisko lokalne/testowe (localhost, *.test, testy.*) → pro, żeby nic nie
 *     przeszkadzało w pracy nad kodem;
 *  4. ważny klucz licencji (LICENSE_KEY, podpis RSA, domena, termin) → edycja z klucza,
 *     chyba że panel licencji przy ostatnim sprawdzeniu online zgłosił unieważnienie
 *     lub zastąpienie klucza (LicenseOnlineCheck);
 *  5. w pozostałych przypadkach → free.
 *
 * Host bierzemy z APP_URL, nie z bieżącego żądania — tak jak Helpdesk, bo klasa
 * działa też w konsoli i schedulerze.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
final class Edition
{
    public const FREE = 'free';

    public const PRO = 'pro';

    /** @var array{edition: string, reason: string}|null */
    private static ?array $resolved = null;

    public static function current(): string
    {
        return self::resolve()['edition'];
    }

    public static function isPro(): bool
    {
        return self::current() === self::PRO;
    }

    /** Czy funkcja (klucz z editions.features) jest dostępna w tej instalacji. */
    public static function allows(string $feature): bool
    {
        return ! self::isPaid($feature) || self::isPro();
    }

    public static function isPaid(string $feature): bool
    {
        return array_key_exists($feature, (array) config('editions.features', []));
    }

    public static function allowsTemplate(string $template): bool
    {
        return self::isPro() || ! in_array($template, (array) config('editions.paid_templates', []), true);
    }

    public static function label(string $feature): string
    {
        return (string) (config("editions.features.{$feature}") ?? $feature);
    }

    /** Komunikat dla zablokowanej akcji (panel i formularze publiczne). */
    public static function lockedMessage(string $feature): string
    {
        return '„'.self::label($feature).'” jest dostępne w wersji płatnej weCMS. '
            .'Dotychczasowe treści możesz przeglądać, ale nie możesz ich zmieniać ani dodawać nowych.';
    }

    /** Skąd wzięła się edycja — do `php artisan edition:status`. */
    public static function reason(): string
    {
        return self::resolve()['reason'];
    }

    /** Czyści zapamiętany wynik (testy, zmiana konfiguracji w locie). */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /** @return array{edition: string, reason: string} */
    private static function resolve(): array
    {
        return self::$resolved ??= self::detect();
    }

    /** @return array{edition: string, reason: string} */
    private static function detect(): array
    {
        $host = strtolower((string) parse_url((string) config('app.url', ''), PHP_URL_HOST));

        foreach ((array) config('editions.always_full_hosts', []) as $fullHost) {
            if ($host === $fullHost || str_ends_with($host, '.'.$fullHost)) {
                return ['edition' => self::PRO, 'reason' => "instalacja {$fullHost} — zawsze pełna wersja"];
            }
        }

        $forced = config('editions.forced');
        if (in_array($forced, [self::FREE, self::PRO], true)) {
            return ['edition' => $forced, 'reason' => 'wymuszone zmienną EDITION'];
        }

        if (self::isLocalHost($host)) {
            return ['edition' => self::PRO, 'reason' => 'środowisko lokalne/testowe'];
        }

        $key = trim((string) config('editions.license_key', ''));
        if ($key === '') {
            return ['edition' => self::FREE, 'reason' => 'brak klucza licencji (LICENSE_KEY)'];
        }

        // Bez klucza publicznego LicenseValidator przepuszcza wszystko (tryb dev) —
        // takiego „potwierdzenia” nie traktujemy jako licencji.
        if (LicenseValidator::isDevMode()) {
            return ['edition' => self::FREE, 'reason' => 'brak LICENSE_PUBLIC_KEY — nie da się zweryfikować klucza'];
        }

        $payload = LicenseValidator::validate($key, (string) config('app.url'));
        if ($payload === null) {
            return ['edition' => self::FREE, 'reason' => 'klucz licencji nieważny, wygasły albo dla innej domeny'];
        }

        if (LicenseOnlineCheck::blocks()) {
            $reason = LicenseOnlineCheck::last()['reason'];

            return ['edition' => self::FREE, 'reason' => 'panel licencji: '.(LicenseOnlineCheck::REASON_LABELS[$reason] ?? $reason)];
        }

        $edition = ($payload['x'] ?? '') === self::FREE ? self::FREE : self::PRO;

        return ['edition' => $edition, 'reason' => 'licencja „'.($payload['x'] ?? '?').'”'
            .(! empty($payload['e']) ? ' ważna do '.$payload['e'] : ' bezterminowa')];
    }

    private static function isLocalHost(string $host): bool
    {
        return in_array($host, ['', 'localhost', '127.0.0.1', '::1', '[::1]'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost')
            || str_starts_with($host, 'testy.');
    }
}
