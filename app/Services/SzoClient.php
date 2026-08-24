<?php

namespace App\Services;

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

    /** Slug formularza po stronie SZO: ustawienie formularza albo domyślny. */
    protected function slugFor(FormDefinition $form): string
    {
        return trim((string) (
            $form->settings['szo_form_slug'] ?? config('szo.default_form', '')
        ));
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
        $aliases = [
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

        $out  = [];
        $data = $submission->data ?? [];

        foreach ($form->normalizedFields() as $field) {
            $key   = $field['key'] ?? '';
            $value = $data[$key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Zgody obsługiwane osobno — nie są danymi kontaktu.
            if (($field['type'] ?? '') === 'checkbox') {
                continue;
            }

            $target = $aliases[$key] ?? $key;

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

        foreach ($form->normalizedFields() as $field) {
            if (($field['type'] ?? '') !== 'checkbox') {
                continue;
            }
            if (! empty($data[$field['key'] ?? ''])) {
                $out[] = $field['key'];
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

    protected function endpoint(): string
    {
        return config('szo.url') . '/api/v1/forms.php';
    }

    /** Komunikat błędu w formie, z której da się coś wywnioskować. */
    protected function errorFrom(int $status, mixed $message): string
    {
        return match ($status) {
            401, 403 => "SZO odrzuciło token (HTTP {$status}). Sprawdź SZO_TOKEN i uprawnienia forms:submit.",
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
