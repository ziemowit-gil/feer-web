<?php

namespace App\Support;

use App\Mail\ScheduleChangeMail;
use App\Models\MeetingSignup;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Opcje strony kontaktowej (adresy, przesyłki, rachunki, spotkania, harmonogram…).
 * Edytowane na stronie typu „Kontakt” w panelu (Strony), zapisywane w ustawieniach witryny.
 */
class ContactSettings
{
    /** Reguły walidacji wszystkich pól formularza kontaktu. */
    public static function rules(): array
    {
        return [
            'show_coordinators' => ['sometimes', 'boolean'],
            'contact_layout' => [
                'nullable',
                Rule::in(array_keys(SiteSetting::CONTACT_LAYOUTS)),
                Rule::notIn(array_diff(SiteSetting::current()->blocked_options['contact_layouts'] ?? [], [SiteSetting::current()->contact_layout])),
            ],
            'contact_office_address' => ['nullable', 'string', 'max:255'],
            'contact_office_city' => ['nullable', 'string', 'max:255'],
            'contact_office_building' => ['nullable', 'string', 'max:255'],
            'contact_office_note' => ['nullable', 'string', 'max:1000'],
            'contact_office_photo_alt' => ['nullable', 'string', 'max:255'],
            'contact_hero_photo' => ['sometimes', 'boolean'],
            'office_photo' => ['nullable', 'image', 'max:4096'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'contact_city' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_office_hours' => ['nullable', 'string', 'max:255'],
            'contact_edelivery_address' => ['nullable', 'string', 'max:120'],
            'contact_correspondence_title' => ['nullable', 'string', 'max:120'],
            'contact_correspondence_note' => ['nullable', 'string', 'max:1000'],
            'contact_shipping_note' => ['nullable', 'string', 'max:255'],
            'contact_paczkomat_code' => ['nullable', 'string', 'max:30'],
            'contact_paczkomat_address' => ['nullable', 'string', 'max:255'],
            'contact_paczkomat_location' => ['nullable', 'string', 'max:255'],
            'contact_shipping_phone' => ['nullable', 'string', 'max:50'],
            'contact_shipping_visible' => ['sometimes', 'boolean'],
            'contact_intro' => ['nullable', 'string', 'max:5000'],
            'contact_bank_accounts' => ['nullable', 'array'],
            'contact_bank_accounts.*.number' => ['nullable', 'string', 'max:80'],
            'contact_bank_accounts.*.purpose' => ['nullable', 'string', 'max:500'],
            'contact_bank_accounts_note' => ['nullable', 'string', 'max:2000'],
            'contact_bank_accounts_layout' => ['nullable', Rule::in(array_keys(SiteSetting::BANK_ACCOUNTS_LAYOUTS))],
            'contact_meeting_title' => ['nullable', 'string', 'max:255'],
            'contact_online_meeting_url' => ['nullable', 'string', 'max:255'],
            'contact_online_meeting_label' => ['nullable', 'string', 'max:100'],
            'contact_online_meeting_text' => ['nullable', 'string', 'max:255'],
            'contact_remote_note' => ['nullable', 'string', 'max:255'],
            'contact_meeting_notify_email' => ['nullable', 'email', 'max:255'],
            'contact_schedule_title' => ['nullable', 'string', 'max:255'],
            'contact_schedule_enabled' => ['sometimes', 'boolean'],
            'contact_no_schedule_note' => ['nullable', 'string', 'max:255'],
            'contact_schedule' => ['nullable', 'array'],
            'contact_schedule.*.type' => ['nullable', Rule::in(['date', 'weekly'])],
            'contact_schedule.*.date' => ['nullable', 'date'],
            'contact_schedule.*.weekday' => ['nullable', 'integer', 'between:1,7'],
            'contact_schedule.*.time' => ['nullable', 'string', 'max:60'],
            'contact_schedule.*.where' => ['nullable', 'string', 'max:255'],
            'contact_schedule.*.note' => ['nullable', 'string', 'max:500'],
            'notify_schedule_change' => ['sometimes', 'boolean'],
            'contact_box_text' => ['nullable', 'string', 'max:1000'],
            'contact_box_link_label' => ['nullable', 'string', 'max:100'],
            'contact_box_link_url' => ['nullable', 'string', 'max:255'],
            'contact_box_visible_from' => ['nullable', 'date'],
            'contact_box_visible_until' => ['nullable', 'date', 'after_or_equal:contact_box_visible_from'],
        ];
    }

    /**
     * Waliduje żądanie i zapisuje opcje kontaktu (w tym zdjęcie biura). Zwraca komunikat
     * o ewentualnym powiadomieniu o zmianie terminu (pusty, gdy go nie zlecono).
     */
    public static function save(Request $request): string
    {
        $data = $request->validate(self::rules());
        $settings = SiteSetting::current();

        // Pola wymagane przez widok publiczny nie mogą zostać wyczyszczone.
        foreach (['contact_address', 'contact_city', 'contact_email'] as $required) {
            if (blank($data[$required] ?? null)) {
                unset($data[$required]);
            }
        }

        foreach (['show_coordinators', 'contact_hero_photo', 'contact_shipping_visible', 'contact_schedule_enabled'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        // Rachunki bankowe: przycinamy pola, odrzucamy wiersze bez numeru
        // (pusty wiersz-zalążek z formularza) i przenumerowujemy listę.
        $data['contact_bank_accounts'] = collect($request->input('contact_bank_accounts', []))
            ->map(fn ($row) => [
                'number' => trim((string) ($row['number'] ?? '')),
                'purpose' => trim((string) ($row['purpose'] ?? '')),
            ])
            ->filter(fn ($row) => $row['number'] !== '')
            ->values()
            ->all();

        // Harmonogram stacjonarny: każdy wpis to konkretna data albo cykliczny
        // dzień tygodnia. Przycinamy pola i odrzucamy wiersze bez daty/dnia.
        $data['contact_schedule'] = collect($request->input('contact_schedule', []))
            ->map(function ($row) {
                $type = ($row['type'] ?? 'date') === 'weekly' ? 'weekly' : 'date';

                return [
                    'type' => $type,
                    'date' => $type === 'date' ? trim((string) ($row['date'] ?? '')) : '',
                    'weekday' => $type === 'weekly' ? (int) ($row['weekday'] ?? 0) : null,
                    'time' => trim((string) ($row['time'] ?? '')),
                    'where' => trim((string) ($row['where'] ?? '')),
                    'note' => trim((string) ($row['note'] ?? '')),
                ];
            })
            ->filter(fn ($row) => ($row['type'] === 'date' && $row['date'] !== '')
                || ($row['type'] === 'weekly' && $row['weekday'] >= 1 && $row['weekday'] <= 7))
            ->values()
            ->all();

        unset($data['office_photo'], $data['notify_schedule_change']);
        $settings->update($data);

        if ($request->hasFile('office_photo')) {
            $settings->addMediaFromRequest('office_photo')->toMediaCollection('office_photo');
        } elseif ($request->boolean('remove_office_photo')) {
            $settings->clearMediaCollection('office_photo');
        }

        // Opcjonalne powiadomienie o zmianie terminu: do osób zapisanych przez
        // formularz „Daj znać, że przyjdziesz” (BCC — nie ujawniamy adresów), z
        // kopią na adres administracyjny. Błąd wysyłki nie cofa zapisu ustawień.
        $notifyMsg = '';
        if ($request->boolean('notify_schedule_change')) {
            $recipients = MeetingSignup::query()->pluck('email')->filter()->unique()->values()->all();
            $copyTo = $settings->meetingNotifyEmail();

            if ($copyTo || $recipients !== []) {
                $items = array_map(fn ($i) => [
                    'when_label' => $i['when_label'],
                    'where' => $i['where'],
                    'note' => $i['note'],
                    'is_next' => $i['is_next'],
                ], $settings->contactScheduleUpcoming());

                $mail = new ScheduleChangeMail($items, $settings->site_name, $settings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy');
                $to = $copyTo ?: $recipients[0];
                $bcc = $copyTo ? $recipients : array_slice($recipients, 1);

                try {
                    $pending = Mail::to($to);
                    if ($bcc !== []) {
                        $pending->bcc($bcc);
                    }
                    $pending->send($mail);

                    $notifyMsg = $recipients !== []
                        ? ' Wysłano powiadomienie o zmianie terminu do '.count($recipients).' zapisanych osób (kopia na adres administracyjny).'
                        : ' Powiadomienie o zmianie terminu wysłano na adres administracyjny (brak zapisanych osób).';
                } catch (\Throwable $e) {
                    $notifyMsg = ' Ustawienia zapisano, ale nie udało się wysłać powiadomienia o zmianie terminu: '.$e->getMessage();
                }
            } else {
                $notifyMsg = ' Nie wysłano powiadomienia — brak adresu i zapisanych osób.';
            }
        }


        return $notifyMsg;
    }
}
