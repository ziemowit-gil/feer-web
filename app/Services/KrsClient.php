<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dane podmiotu z Krajowego Rejestru Sądowego — otwarte API Ministerstwa Sprawiedliwości
 * (https://prs.ms.gov.pl/krs/openApi, odpis aktualny). Najpierw rejestr stowarzyszeń, fundacji
 * i innych organizacji (S), potem rejestr przedsiębiorców (P). Wyniki trzymamy 12 h w cache.
 */
class KrsClient
{
    public const BASE_URL = 'https://api-krs.ms.gov.pl/api/krs/OdpisAktualny/';

    /** Dane, którymi formularz może uzupełnić pola (klucz → etykieta w kreatorze). */
    public const FILL_KEYS = [
        'nazwa'           => 'Nazwa podmiotu',
        'forma_prawna'    => 'Forma prawna',
        'nip'             => 'NIP',
        'regon'           => 'REGON',
        'adres'           => 'Adres siedziby (pełny)',
        'ulica'           => 'Ulica i numer',
        'kod_pocztowy'    => 'Kod pocztowy',
        'miejscowosc'     => 'Miejscowość',
        'wojewodztwo'     => 'Województwo',
        'email'           => 'E-mail z KRS',
        'www'             => 'Strona internetowa',
        'opp'             => 'Status OPP (Tak/Nie)',
        'data_rejestracji' => 'Data wpisu do KRS',
    ];

    /** Numer KRS w postaci 10 cyfr (z zerami wiodącymi) albo null, gdy to nie numer KRS. */
    public static function normalize(string $number): ?string
    {
        $digits = preg_replace('/\D/', '', $number);

        if ($digits === '' || strlen($digits) > 10 || (int) $digits === 0) {
            return null;
        }

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{status: 'found'|'not_found'|'error', data?: array<string, string>}
     */
    public function lookup(string $number): array
    {
        $krs = self::normalize($number);
        if (! $krs) {
            return ['status' => 'not_found'];
        }

        $cached = Cache::get('krs:'.$krs);
        if ($cached) {
            return $cached;
        }

        foreach (['S', 'P'] as $register) {
            try {
                $response = Http::timeout(8)->connectTimeout(4)->acceptJson()
                    ->get(self::BASE_URL.$krs, ['rejestr' => $register, 'format' => 'json']);
            } catch (\Throwable $e) {
                Log::warning('KRS: brak połączenia z API', ['krs' => $krs, 'error' => $e->getMessage()]);

                return ['status' => 'error'];
            }

            if ($response->successful() && is_array($response->json('odpis'))) {
                $result = ['status' => 'found', 'data' => self::map($response->json('odpis'))];
                Cache::put('krs:'.$krs, $result, now()->addHours(12));

                return $result;
            }

            if (! in_array($response->status(), [400, 404], true)) {
                Log::warning('KRS: nieoczekiwana odpowiedź API', ['krs' => $krs, 'status' => $response->status()]);

                return ['status' => 'error'];
            }
        }

        return ['status' => 'not_found'];
    }

    /**
     * Spłaszcza odpis do pól formularza.
     *
     * @param  array<string, mixed>  $odpis
     * @return array<string, string>
     */
    public static function map(array $odpis): array
    {
        $d1 = data_get($odpis, 'dane.dzial1', []);
        $a = data_get($d1, 'siedzibaIAdres.adres', []);
        $seat = data_get($d1, 'siedzibaIAdres.siedziba', []);

        $street = trim(implode(' ', array_filter([
            $a['ulica'] ?? null,
            trim(($a['nrDomu'] ?? '').(filled($a['nrLokalu'] ?? null) ? '/'.$a['nrLokalu'] : '')),
        ])));
        $city = $a['miejscowosc'] ?? ($seat['miejscowosc'] ?? '');
        $postal = $a['kodPocztowy'] ?? '';
        $regon = (string) data_get($d1, 'danePodmiotu.identyfikatory.regon', '');
        // REGON 14-cyfrowy z końcówką 00000 to w praktyce REGON 9-cyfrowy.
        if (strlen($regon) === 14 && str_ends_with($regon, '00000')) {
            $regon = substr($regon, 0, 9);
        }
        $www = (string) data_get($d1, 'siedzibaIAdres.adresStronyInternetowej', '');

        return array_map(fn ($v) => trim((string) $v), [
            'krs'              => (string) data_get($odpis, 'naglowekA.numerKRS', ''),
            'nazwa'            => data_get($d1, 'danePodmiotu.nazwa', ''),
            'forma_prawna'     => data_get($d1, 'danePodmiotu.formaPrawna', ''),
            'nip'              => data_get($d1, 'danePodmiotu.identyfikatory.nip', ''),
            'regon'            => $regon,
            'ulica'            => $street,
            'kod_pocztowy'     => $postal,
            'miejscowosc'      => $city,
            'wojewodztwo'      => $seat['wojewodztwo'] ?? '',
            'adres'            => trim($street.($street !== '' ? ', ' : '').trim($postal.' '.$city)),
            'email'            => strtolower((string) data_get($d1, 'siedzibaIAdres.adresPocztyElektronicznej', '')),
            'www'              => $www !== '' && ! preg_match('~^https?://~i', $www) ? 'https://'.strtolower($www) : strtolower($www),
            'opp'              => data_get($d1, 'danePodmiotu.czyPosiadaStatusOPP') ? 'Tak' : 'Nie',
            'data_rejestracji' => (string) data_get($odpis, 'naglowekA.dataRejestracjiWKRS', ''),
        ]);
    }
}
