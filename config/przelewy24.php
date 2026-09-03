<?php

// Integracja płatności Przelewy24 (REST API v3) dla modułu Sklep.
// Dane dostępowe pochodzą z panelu sprzedawcy P24 — dla środowiska sandbox
// zakładanego na https://sandbox.przelewy24.pl. Przełączenie na produkcję to
// zmiana PRZELEWY24_SANDBOX=false (i podmiana pozostałych wartości na produkcyjne).
return [

    'merchant_id' => env('PRZELEWY24_MERCHANT_ID'),

    'pos_id' => env('PRZELEWY24_POS_ID'),

    // Klucz CRC z panelu P24 (Ustawienia sklepu) — używany do podpisywania żądań.
    'crc' => env('PRZELEWY24_CRC'),

    // Klucz REST API (Ustawienia sklepu → Klucz do raportów) — inny niż CRC,
    // używany do autoryzacji Basic Auth w wywołaniach API.
    'api_key' => env('PRZELEWY24_API_KEY'),

    'sandbox' => env('PRZELEWY24_SANDBOX', true),

];
