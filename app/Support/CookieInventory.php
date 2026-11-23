<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Pliki cookies i dane w przeglądarce, które ta strona naprawdę zapisuje — liczone z konfiguracji, żeby strona
 * /informacja-o-cookies nie rozjeżdżała się z rzeczywistością (np. statystyki pojawiają się tylko, gdy są ustawione
 * w panelu, i ładują się dopiero po zgodzie — partials/analytics).
 *
 * Grupa: [tytuł, opis, wymaga zgody?, lista wpisów]; wpis: name, kind (cookie|localStorage), provider, purpose, lifetime.
 */
final class CookieInventory
{
    /** @return list<array{key: string, title: string, description: string, consent: bool, items: list<array<string, string>>}> */
    public static function for(SiteSetting $site): array
    {
        $groups = [
            [
                'key' => 'necessary',
                'title' => 'Niezbędne',
                'description' => 'Bez nich strona nie działa poprawnie (formularze, logowanie, zapamiętanie Twojej decyzji o cookies). Nie wymagają zgody.',
                'consent' => false,
                'items' => [
                    self::item((string) config('session.cookie'), 'cookie', 'ta strona', 'Sesja: łączy kolejne odsłony, chroni formularze i utrzymuje zalogowanie redakcji.', self::sessionLifetime()),
                    self::item('XSRF-TOKEN', 'cookie', 'ta strona', 'Ochrona formularzy przed podszywaniem się (CSRF).', self::sessionLifetime()),
                    self::item('remember_web_…', 'cookie', 'ta strona', 'Tylko po zaznaczeniu „Zapamiętaj mnie” przy logowaniu do panelu.', 'do wylogowania'),
                    self::item('feer_cookie_consent, feer_cookie_consent_id', 'localStorage', 'ta strona', 'Twoja decyzja o cookies i losowy identyfikator tej decyzji w rejestrze zgód (bez danych osobowych).', 'do usunięcia danych przeglądarki'),
                ],
            ],
            [
                'key' => 'preferences',
                'title' => 'Ułatwienia dostępu',
                'description' => 'Zapisują się tylko wtedy, gdy zmienisz ustawienia w pasku dostępności (rozmiar tekstu, kontrast, odstępy). Dane zostają w Twojej przeglądarce.',
                'consent' => false,
                'items' => [
                    self::item('a11y-…', 'localStorage', 'ta strona', 'Ustawienia paska dostępności: rozmiar tekstu, kontrast, odstępy, fokus.', 'do usunięcia danych przeglądarki'),
                ],
            ],
        ];

        $stats = [];
        if (filled($site->ga_measurement_id ?? null)) {
            $stats[] = self::item('_ga, _ga_…', 'cookie', 'Google (Google Analytics 4)', 'Anonimowe statystyki odwiedzin (skrócony adres IP).', 'do 2 lat');
        }
        $matomo = method_exists($site, 'matomoConfig') ? $site->matomoConfig() : null;
        if ($matomo && empty($matomo['cookieless'])) {
            $host = parse_url((string) ($matomo['url'] ?? ''), PHP_URL_HOST) ?: 'serwer statystyk';
            $stats[] = self::item('_pk_id…, _pk_ses…', 'cookie', 'Matomo ('.$host.')', 'Statystyki odwiedzin na naszym serwerze statystyk.', '_pk_id do 13 miesięcy, _pk_ses 30 minut');
        }
        if (filled(trim((string) ($site->clarity_project_id ?? '')))) {
            $stats[] = self::item('_clck, _clsk, CLID, MUID', 'cookie', 'Microsoft (Clarity)', 'Analiza korzystania ze strony (mapy kliknięć, nagrania sesji bez wpisywanych danych).', 'od 1 dnia do 1 roku');
        }
        if ($stats !== []) {
            $groups[] = [
                'key' => 'statistics',
                'title' => 'Statystyki',
                'description' => 'Ładują się dopiero po kliknięciu „Akceptuję” w banerze. Po „Odrzucam” nie są zapisywane.',
                'consent' => true,
                'items' => $stats,
            ];
        }

        return $groups;
    }

    /** Czy strona używa czegokolwiek, co wymaga zgody (statystyki). */
    public static function needsConsent(SiteSetting $site): bool
    {
        return collect(self::for($site))->contains(fn (array $g) => $g['consent']);
    }

    /** @return array<string, string> */
    private static function item(string $name, string $kind, string $provider, string $purpose, string $lifetime): array
    {
        return compact('name', 'kind', 'provider', 'purpose', 'lifetime');
    }

    private static function sessionLifetime(): string
    {
        $minutes = (int) config('session.lifetime', 120);
        if (config('session.expire_on_close')) {
            return 'do zamknięcia przeglądarki';
        }

        return $minutes % 60 === 0 ? ($minutes / 60).' godz.' : $minutes.' min';
    }
}
