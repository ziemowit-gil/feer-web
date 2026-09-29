<?php

/**
 * Połączenie z systemem SZO (feerSZO) — przekazywanie zgłoszeń z formularzy
 * do CRM-a.
 *
 * SZO stoi pod INNYM adresem niż ten CMS, więc łączymy się serwer-serwer:
 * token nigdy nie trafia do przeglądarki. Gdyby formularz wołał API SZO
 * bezpośrednio z JavaScriptu, token byłby publiczny razem z kodem strony.
 */
return [
    // Wyłącznik główny. Bez niego (albo bez tokenu) formularze działają jak
    // dotąd — zgłoszenie zapisuje się lokalnie i nigdzie nie leci.
    'enabled' => env('SZO_ENABLED', false),

    // Adres bazowy SZO, bez ukośnika na końcu, np. https://szo.feer.org.pl
    'url' => rtrim((string) env('SZO_URL', ''), '/'),

    // Token API z uprawnieniami forms:read i forms:submit (Admin → API w SZO).
    // Celowo NIE crm:write — ten token ma móc tylko przyjmować zgłoszenia.
    'token' => env('SZO_TOKEN', ''),

    // Krótki limit czasu: użytkownik czeka na potwierdzenie wysłania formularza,
    // a niedostępne SZO nie może blokować mu strony. Nieudane zgłoszenia
    // dosyła polecenie `szo:push-submissions`.
    'timeout' => (int) env('SZO_TIMEOUT', 5),

    // Domyślny slug formularza w SZO, gdy pojedynczy formularz go nie ustawia.
    'default_form' => env('SZO_DEFAULT_FORM', ''),

    // Darowizny ze strony (Przelewy24) → rejestr darowizn SZO. Token musi mieć
    // dodatkowo uprawnienie donations:submit. Formularz SZO (opcjonalny) służy
    // do zapisu zgód darczyńcy: jego zgody muszą mieć id „rodo" i „newsletter".
    'donation_form' => env('SZO_DONATION_FORM', 'darowizna'),
    'donation_purpose' => env('SZO_DONATION_PURPOSE', 'Darowizna na cele statutowe'),
];
