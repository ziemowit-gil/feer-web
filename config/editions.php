<?php

/**
 * Edycje weCMS: darmowa (free) i płatna (pro). Patrz App\Support\Edition.
 *
 * Wszystko, czego nie ma na listach poniżej, jest w wersji darmowej — dostępność
 * (WCAG), bezpieczeństwo (2FA, aktualizacje) i eksport treści zawsze zostają
 * darmowe. Po wygaśnięciu licencji nic nie znika: treści płatnych modułów są dalej
 * widoczne publicznie i do odczytu w panelu, blokowane są tylko zapisy i akcje
 * (wysyłka, płatności, skany) — patrz EnsureEditionAllows / EnsureModuleEnabled.
 */
return [
    // Wymuszenie edycji (free|pro) — do testów i instalacji demo. Puste = wg licencji.
    // Nie działa na hostach z always_full_hosts.
    'forced' => env('EDITION'),

    // Instalacje, które ZAWSZE mają pełną wersję (łącznie z subdomenami),
    // niezależnie od klucza licencji — rozpoznawane po hoście z APP_URL.
    'always_full_hosts' => ['feer.org.pl'],

    // Klucz licencji weCMS (WCMS-…, podpis RSA, patrz LicenseValidator i
    // scripts/generate-license.php). Zapisywany przez instalator. Pole „x”
    // klucza to edycja: każda poza „free” daje wersję płatną.
    'license_key' => env('LICENSE_KEY', ''),

    // Panel licencji: sprawdzanie online (unieważnienie, zastąpienie klucza). Raz dziennie
    // (`php artisan license:check`); niedostępny panel niczego nie zmienia. Puste = wyłączone.
    'check_url' => env('LICENSE_CHECK_URL', 'https://cms2.feer.org.pl/api/v1/licenses/check'),
    'check_timeout' => (int) env('LICENSE_CHECK_TIMEOUT', 5),

    // Funkcje płatne: klucz → etykieta do komunikatów. Kluczem modułu jest jego
    // identyfikator z middleware `module:` (SiteSetting::MODULES / module.json),
    // pozostałe funkcje sprawdza middleware `edition:<klucz>` albo Edition::allows().
    'features' => [
        // Moduły
        'bip' => 'BIP (Biuletyn Informacji Publicznej)',
        'newsletter' => 'Newsletter',
        'sklep' => 'Sklep z płatnościami Przelewy24',
        'forms' => 'Kreator formularzy',
        'polls' => 'Ankiety',
        'volunteering' => 'Wolontariat',
        'jobs' => 'Oferty pracy',
        'cooperation' => 'Zgłoszenia współpracy',
        'reports' => 'Sprawozdania',
        'strategy' => 'Strategia organizacji',
        'authorizations' => 'Rejestr pełnomocnictw',

        // Pozostałe funkcje
        'donations' => 'Darowizny online (Przelewy24)',
        'campaigns' => 'Kampanie',
        'content_templates' => 'Szablony treści',
        'docx_import' => 'Import z plików DOCX',
        'multisite' => 'Sieć witryn (federacja, Ośrodki)',
        'tasks' => 'Zadania redakcyjne',
        'user_groups' => 'Grupy użytkowników',
        'invitations' => 'Zaproszenia do strefy członkowskiej',
        'wcag_scan' => 'Skaner WCAG całego serwisu',
        'link_check' => 'Sprawdzanie martwych linków',

        // Do podpięcia (zdefiniowane, jeszcze nie egzekwowane w kodzie)
        'approvals' => 'Akceptacja treści',
        'revisions' => 'Pełna historia zmian',
        'brand_assets' => 'Strefa marki',
        'etr' => 'Tekst łatwy do czytania (ETR)',
        'podcast_subscribers' => 'Podcasty dla subskrybentów',
        'szo' => 'Integracja z SZO',
        'helpdesk_tickets' => 'Zgłoszenia do Helpdesku z gwarancją odpowiedzi',
    ],

    // Szablony witryny dostępne tylko w wersji płatnej (klucze SiteSetting::SITE_TEMPLATES).
    // Witryna, która już ma taki szablon, dalej z nim działa — blokowany jest tylko wybór.
    'paid_templates' => ['municipality', 'federation', 'wrzos', 'vm', 'ngo_mix', 'ngo_3', 'feer'],
];
