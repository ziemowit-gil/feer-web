<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klient API systemu SZO (feerSZO) — przekazywanie zgłoszeń z formularzy do CRM.
 *
 * ─── DLACZEGO TAK, A NIE PROŚCIEJ ──────────────────────────────────────────
 *
 * SZO jest pod innym adresem, więc każde wywołanie to żądanie przez sieć, które
 * może się nie udać: SZO akurat wdraża nową wersję, sieć przycięła, certyfikat
 * wygasł. Zgłoszenie użytkownika NIE MOŻE od tego zależeć — jest już zapisane
 * w `form_submissions` i to jest źródło prawdy. Wysyłka do SZO jest dodatkiem,
 * który wolno ponowić.
 *
 * Stąd trzy decyzje:
 *   1. krótki timeout (domyślnie 5 s) — użytkownik czeka na potwierdzenie,
 *   2. żaden wyjątek nie wychodzi na zewnątrz — błąd ląduje w `szo_error`,
 *   3. `external_id` = id zgłoszenia w CMS — SZO rozpoznaje po nim ponowienie
 *      i nie zakłada drugiego kontaktu ani drugiej wiadomości w Skrzynce.
 */
class SzoClient
{
    public function enabled(): bool
    {
        return (bool) config('szo.enabled')
            && filled(config('szo.url'))
            && filled(config('szo.token'));
    }

    /**
     * Diagnostyka połączenia (panel: Ustawienia → Integracje → SZO): sprawdza
     * adres i token zapytaniem o listę formularzy, bez zapisywania czegokolwiek.
     *
     * @return array{ok: bool, level: 'success'|'warning'|'error', message: string}
     */
    public static function diagnose(string $url, string $token): array
    {
        $url = rtrim(trim($url), '/');
        if ($url === '') {
            return ['ok' => false, 'level' => 'error', 'message' => 'Wpisz adres SZO, np. https://szo.feer.org.pl.'];
        }
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
            return ['ok' => false, 'level' => 'error', 'message' => 'Adres SZO musi zaczynać się od https:// (lub http://).'];
        }

        // Lista klauzul (publiczna) — jej brak nie przerywa diagnozy: token sprawdzamy i tak.
        $clausesNote = '';
        try {
            $public = self::fetchClausesList($url, 10);
            if (! $public->successful()) {
                $clausesNote = " Uwaga: lista klauzul RODO niedostępna (HTTP {$public->status()}) — import klauzul nie zadziała.";
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'level' => 'error', 'message' => 'Nie udało się połączyć z SZO: ' . $e->getMessage() . ' Sprawdź adres (sam host, np. https://szo.feer.org.pl).'];
        }

        if (trim($token) === '') {
            return ['ok' => false, 'level' => 'warning', 'message' => 'Adres SZO odpowiada, ale bez tokenu nie da się przekazywać zgłoszeń ani darowizn.' . $clausesNote];
        }

        try {
            $forms = Http::withToken($token)->timeout(10)->acceptJson()->get($url . '/api/v1/forms.php');
        } catch (Throwable $e) {
            return ['ok' => false, 'level' => 'error', 'message' => 'Nie udało się połączyć z API SZO: ' . $e->getMessage()];
        }

        if (in_array($forms->status(), [401, 403], true)) {
            return ['ok' => false, 'level' => 'error', 'message' => "SZO odrzuciło token (HTTP {$forms->status()}). Sprawdź token i uprawnienia forms:read, forms:submit (dla darowizn także donations:submit)." . $clausesNote];
        }
        if (! $forms->successful()) {
            return ['ok' => false, 'level' => 'error', 'message' => "SZO odpowiedziało HTTP {$forms->status()} dla /api/v1/forms.php — sprawdź adres (sam host, bez /api)." . $clausesNote];
        }

        $count = count((array) $forms->json('forms', []));

        return ['ok' => true, 'level' => $clausesNote === '' ? 'success' : 'warning', 'message' => "Połączenie działa. Token ma dostęp do API; dostępnych formularzy w SZO: {$count}." . $clausesNote];
    }

    /** Lista formularzy zdefiniowanych w SZO — do wyboru w panelu CMS. */
    public function forms(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        try {
            $res = $this->request()->get($this->endpoint());

            return $res->successful() ? ($res->json('forms') ?? []) : [];
        } catch (Throwable $e) {
            Log::warning('[SZO] Nie udało się pobrać listy formularzy: ' . $e->getMessage());

            return [];
        }
    }

    /** Jeden formularz SZO ze szczegółami (pola, zgody) albo null, gdy go nie ma lub SZO nie odpowiada. */
    public function form(string $slug): ?array
    {
        if (! $this->enabled() || trim($slug) === '') {
            return null;
        }

        try {
            $res = $this->request()->get($this->endpoint(), ['slug' => trim($slug)]);

            return $res->successful() && is_array($res->json('form')) ? $res->json('form') : null;
        } catch (Throwable $e) {
            Log::warning('[SZO] Nie udało się pobrać formularza ' . $slug . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Wysyła zgłoszenie do SZO i zapisuje wynik na rekordzie zgłoszenia.
     *
     * Zwraca true, gdy SZO przyjęło zgłoszenie (także gdy rozpoznało je jako
     * ponowienie — wtedy po prostu nie ma nic do zrobienia).
     */
    public function pushSubmission(FormSubmission $submission): bool
    {
        $form = $submission->form;

        if (! $this->enabled() || ! $form) {
            return false;
        }

        $slug = $this->slugFor($form);
        if (blank($slug)) {
            // Formularz świadomie niepodpięty do SZO — to nie jest błąd.
            return false;
        }

        try {
            $res = $this->request()->post($this->endpoint(), [
                'form'     => $slug,
                'data'     => $this->mapData($form, $submission),
                'consents' => $this->consents($form, $submission),
                'custom'   => (object) $this->customData($form, $submission),
                'fields'   => $this->fieldsMeta($form),
                'meta'     => [
                    'ip'          => $submission->ip_address,
                    'external_id' => (string) $submission->id,
                    'url'         => config('app.url') . '/formularz/' . $form->slug,
                ],
            ]);

            if ($res->successful() && $res->json('ok')) {
                $submission->forceFill([
                    'szo_contact_id' => $res->json('contact_id'),
                    'szo_synced_at'  => now(),
                    'szo_error'      => null,
                ])->save();

                return true;
            }

            $this->fail($submission, $this->errorFrom($res->status(), $res->json('error') ?? $res->body()));
        } catch (Throwable $e) {
            $this->fail($submission, $e->getMessage());
        }

        return false;
    }

    /**
     * Przekazuje OPŁACONĄ darowiznę do rejestru darowizn SZO
     * (POST /api/v1/donations.php, uprawnienie donations:submit).
     *
     * Tak samo jak zgłoszenia: nic nie rzuca, wynik ląduje w kolumnach szo_*,
     * a `external_id` (session_id) sprawia, że ponowienie nie zdubluje wpłaty.
     */
    public function pushDonation(Donation $donation): bool
    {
        if (! $this->enabled() || ! $donation->isPaid()) {
            return false;
        }

        $consents = ['rodo'];
        if ($donation->consent_newsletter) {
            $consents[] = 'newsletter';
        }

        try {
            $res = $this->request()->post(config('szo.url') . '/api/v1/donations.php', array_filter([
                'external_id'   => 'p24:' . $donation->session_id,
                'amount'        => round($donation->amount_grosze / 100, 2),
                'currency'      => $donation->currency,
                'donation_date' => ($donation->paid_at ?? now())->toDateString(),
                'channel'       => 'p24',
                'bank_ref'      => $donation->p24_order_id ? (string) $donation->p24_order_id : null,
                'purpose'       => config('szo.donation_purpose'),
                'is_anonymous'  => $donation->is_anonymous,
                'donor'         => array_filter([
                    'imie_nazwisko' => $donation->fullName(),
                    'email'         => $donation->email,
                    'telefon'       => $donation->phone,
                ]),
                'form'          => config('szo.donation_form') ?: null,
                'consents'      => $consents,
                'meta'          => [
                    'ip'  => $donation->ip_address,
                    'url' => route('donation.show'),
                ],
            ], fn ($v) => $v !== null));

            if ($res->successful() && $res->json('ok')) {
                $donation->forceFill([
                    'szo_donation_id' => $res->json('donation_id'),
                    'szo_contact_id'  => $res->json('contact_id') ?: null,
                    'szo_synced_at'   => now(),
                    'szo_error'       => null,
                ])->save();

                return true;
            }

            $error = $this->errorFrom($res->status(), $res->json('error') ?? $res->body());
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $donation->forceFill(['szo_error' => mb_substr($error, 0, 1000)])->save();
        Log::warning("[SZO] Darowizna {$donation->id} nieprzekazana: {$error}");

        return false;
    }

    /** Slug formularza po stronie SZO: ustawienie formularza albo domyślny. */
    protected function slugFor(FormDefinition $form): string
    {
        return trim((string) (
            $form->settings['szo_form_slug'] ?? config('szo.default_form', '')
        ));
    }

    /** Klucze pól CMS → klucze kartoteki CRM w SZO (typowe warianty etykiet). */
    protected function aliases(): array
    {
        return [
            'imie_i_nazwisko' => 'imie_nazwisko',
            'imie_nazwisko'   => 'imie_nazwisko',
            'imie'            => 'imie_nazwisko',
            'nazwa'           => 'imie_nazwisko',
            'nazwisko'        => 'nazwisko',
            'email'           => 'email',
            'e_mail'          => 'email',
            'adres_e_mail'    => 'email',
            'telefon'         => 'telefon',
            'numer_telefonu'  => 'telefon',
            'organizacja'     => 'organizacja',
            'firma'           => 'organizacja',
            'nazwa_firmy'     => 'organizacja',
            'wiadomosc'       => 'notatka',
            'tresc'           => 'notatka',
            'tresc_wiadomosci'=> 'notatka',
            'opis'            => 'notatka',
            'pytanie'         => 'notatka',
        ];
    }

    /**
     * Opis pól wysyłany razem ze zgłoszeniem: klucz (po mapowaniu jak w mapData), etykieta,
     * typ i opcje. SZO dopisuje do swojego formularza pola, których jeszcze nie ma
     * (pole kartoteki albo pole dodatkowe CRM), żeby wartości nie przepadały.
     */
    protected function fieldsMeta(FormDefinition $form): array
    {
        $aliases = $this->aliases();
        $out = [];

        foreach ($form->inputFields() as $field) {
            $type = $field['type'] ?? 'text';
            // Zgody idą osobno (consents); załącznik — jako tekst (nazwa pliku).
            if (in_array($type, ['checkbox', 'consent'], true)) {
                continue;
            }
            if ($type === 'file') {
                $type = 'text';
            }
            // Pola lustrzane formularza SZO znają swój klucz w SZO — pola dodatkowe CRM idą osobno (custom).
            if (str_starts_with((string) ($field['szo_name'] ?? ''), 'custom:')) {
                continue;
            }
            $key = $field['szo_name'] ?? $aliases[$field['key']] ?? $field['key'];
            if (isset($out[$key])) {
                continue;
            }
            $out[$key] = array_filter([
                'name'    => $key,
                'label'   => $key === 'notatka' ? 'Wiadomość / Notatka' : (string) ($field['label'] ?? $key),
                'type'    => $type,
                'options' => FormDefinition::fieldOptions($field) ?: null,
            ], fn ($v) => $v !== null);
        }

        return array_values($out);
    }

    /**
     * Mapuje pola zgłoszenia na klucze, których oczekuje CRM.
     *
     * Klucze pól w CMS pochodzą z etykiet (Str::slug), więc „Imię i nazwisko"
     * daje `imie_i_nazwisko`, a CRM oczekuje `imie_nazwisko`. Tabela poniżej
     * łapie typowe warianty; czego nie rozpozna, leci pod własnym kluczem —
     * CRM odrzuci nieznane pola, ale zgłoszenie i tak trafi do Skrzynki
     * z pełną treścią, więc nic nie ginie.
     */
    protected function mapData(FormDefinition $form, FormSubmission $submission): array
    {
        $aliases = $this->aliases();

        $out  = [];
        $data = $submission->data ?? [];

        foreach ($form->inputFields() as $field) {
            $key   = $field['key'] ?? '';
            $value = $data[$key] ?? null;

            // Załączniki: sam plik zostaje w CMS-ie (pobieranie z panelu) — do CRM idzie nazwa i rozmiar, bez ścieżki.
            if (($field['type'] ?? '') === 'file') {
                $value = is_array($value) ? FormDefinition::displayValue($field, $value) . ' — plik w panelu CMS' : null;
                if (! $value) {
                    continue;
                }
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            // Osoba kontaktowa: do CRM idzie imię i działanie, nie sam adres koordynatora.
            if (($field['type'] ?? '') === 'coordinator') {
                $value = FormDefinition::displayValue($field, $value);
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Zgody obsługiwane osobno — nie są danymi kontaktu.
            if (in_array($field['type'] ?? '', ['checkbox', 'consent'], true)) {
                continue;
            }

            if (str_starts_with((string) ($field['szo_name'] ?? ''), 'custom:')) {
                continue;
            }
            $target = $field['szo_name'] ?? $aliases[$key] ?? $key;

            // Kilka pól tekstowych zmapowanych na notatkę sklejamy, zamiast
            // pozwolić ostatniemu nadpisać poprzednie.
            if ($target === 'notatka' && isset($out['notatka'])) {
                $out['notatka'] .= "\n\n" . ($field['label'] ?? $key) . ":\n" . $value;
            } else {
                $out[$target] = $value;
            }
        }

        return $out;
    }

    /** Zaznaczone zgody (checkboxy) — identyfikatory zgodne z definicją w SZO. */
    protected function consents(FormDefinition $form, FormSubmission $submission): array
    {
        $data = $submission->data ?? [];
        $out  = [];

        foreach ($form->inputFields() as $field) {
            if (! in_array($field['type'] ?? '', ['checkbox', 'consent'], true)) {
                continue;
            }
            if (! empty($data[$field['key'] ?? ''])) {
                $out[] = $field['szo_name'] ?? $field['key'];
            }
        }

        return $out;
    }

    /** Pola dodatkowe CRM (lustro formularza SZO: szo_name = „custom:{id definicji}”) — po id definicji. */
    protected function customData(FormDefinition $form, FormSubmission $submission): array
    {
        $data = $submission->data ?? [];
        $out  = [];

        foreach ($form->inputFields() as $field) {
            if (! preg_match('/^custom:(\d+)$/', (string) ($field['szo_name'] ?? ''), $m)) {
                continue;
            }
            $value = $data[$field['key']] ?? null;
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if (filled($value)) {
                $out[$m[1]] = (string) $value;
            }
        }

        return $out;
    }

    protected function request()
    {
        return Http::withToken(config('szo.token'))
            ->timeout((int) config('szo.timeout', 5))
            ->acceptJson()
            ->asJson();
    }

    /** Bezpośredni adres listy klauzul w SZO — gdy serwer (np. nginx przed Apache) nie przepuszcza /klauzule.json do reguły .htaccess. */
    public const CLAUSES_DIRECT_PATH = '/modules/gdpr_clauses/public/list.php';

    /**
     * Pobiera listę klauzul: /klauzule.json, a gdy serwer go nie obsłuży (403/404 — nginx
     * serwuje *.json jako pliki statyczne i nie dochodzi do reguły .htaccess) — alias
     * /klauzule, a na końcu adres bezpośredni skryptu.
     */
    public static function fetchClausesList(string $base, int $timeout): \Illuminate\Http\Client\Response
    {
        $base = rtrim($base, '/');
        $res = null;
        // Kolejno: /klauzule.json, alias bez rozszerzenia /klauzule (SZO ≥ 2026-10), adres bezpośredni skryptu.
        foreach (['/klauzule.json', '/klauzule', self::CLAUSES_DIRECT_PATH] as $path) {
            $res = Http::timeout($timeout)->acceptJson()->get($base . $path);
            if ($res->successful() && is_array($res->json('clauses'))) {
                return $res;
            }
        }

        return $res;
    }

    /**
     * Opublikowane klauzule RODO z SZO (GET /klauzule.json, zapasowo adres bezpośredni).
     *
     * Wymaga tylko SZO_URL — to publiczne dane (te same co /klauzula/{slug}),
     * więc bez tokenu i niezależnie od SZO_ENABLED (który dotyczy wysyłki
     * formularzy). Import działa w tle, stąd dłuższy limit czasu niż dla
     * formularzy. Rzuca RuntimeException z czytelnym powodem — panel pokazuje
     * go administratorowi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function gdprClauses(): array
    {
        $base = (string) config('szo.url');
        if ($base === '') {
            throw new \RuntimeException('Brak adresu SZO (Ustawienia → Integracje → SZO lub SZO_URL w .env) — nie wiadomo, skąd pobrać klauzule.');
        }

        try {
            $res = self::fetchClausesList($base, max(15, (int) config('szo.timeout', 5)));
        } catch (Throwable $e) {
            throw new \RuntimeException('Nie udało się połączyć z SZO: ' . $e->getMessage(), 0, $e);
        }

        if (! $res->successful()) {
            throw new \RuntimeException("SZO odpowiedziało HTTP {$res->status()} dla listy klauzul (/klauzule.json i " . self::CLAUSES_DIRECT_PATH . ').');
        }
        if (! $res->json('ok') || ! is_array($res->json('clauses'))) {
            throw new \RuntimeException('SZO zwróciło nieoczekiwaną odpowiedź zamiast listy klauzul.');
        }

        return $res->json('clauses');
    }

    protected function endpoint(): string
    {
        return config('szo.url') . '/api/v1/forms.php';
    }

    /** Komunikat błędu w formie, z której da się coś wywnioskować. */
    protected function errorFrom(int $status, mixed $message): string
    {
        return match ($status) {
            401, 403 => "SZO odrzuciło token (HTTP {$status}). Sprawdź token (Ustawienia → Integracje → SZO) i uprawnienia (forms:submit, a dla darowizn donations:submit).",
            404      => 'SZO nie zna formularza o tym slugu — sprawdź „Slug formularza w SZO".',
            422      => 'SZO odrzuciło dane zgłoszenia: ' . (is_string($message) ? $message : json_encode($message)),
            default  => "SZO odpowiedziało HTTP {$status}: " . (is_string($message) ? $message : json_encode($message)),
        };
    }

    protected function fail(FormSubmission $submission, string $error): void
    {
        Log::warning('[SZO] Zgłoszenie #' . $submission->id . ' nieprzekazane: ' . $error);

        $submission->forceFill([
            'szo_error'     => mb_substr($error, 0, 500),
            'szo_synced_at' => null,
        ])->save();
    }
}
