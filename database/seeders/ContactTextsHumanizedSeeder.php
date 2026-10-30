<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów strony kontaktu zapisanych w ustawieniach witryny: wstęp, godziny pracy i notatka przy rachunkach
 * bankowych. Fakty bez zmian; usunięte zdanie-slogan, puste kolumny z edytora i myślniki.
 *
 * Idempotentny: każde pole jest podmieniane tylko wtedy, gdy nadal ma pierwotne brzmienie, więc seeder nie nadpisze
 * zmian zrobionych później w panelu.
 *
 *   php artisan db:seed --class=ContactTextsHumanizedSeeder --force
 */
class ContactTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SiteSetting::first();

        if (! $settings) {
            $this->command?->warn('Pominięto: brak ustawień witryny.');

            return;
        }

        if (str_contains(str_replace("\u{00A0}", ' ', html_entity_decode((string) $settings->contact_intro)), 'Każda rozmowa to krok w stronę świata bez barier')) {
            $settings->contact_intro = '<p>Masz pytania o nasze działania albo chcesz dołączyć do realizowanych projektów? <strong>Odezwij się.</strong></p>';
        }

        if ($settings->contact_office_hours === 'Poniedziałek - Środa 10:00 - 12:00') {
            $settings->contact_office_hours = 'Od poniedziałku do środy, 10:00 do 12:00';
        }

        if (str_starts_with((string) $settings->contact_bank_accounts_note, 'Opłaty za szkolenia firm i osób prawnych, a także')) {
            $settings->contact_bank_accounts_note = 'Opłaty za szkolenia firm i osób prawnych oraz za inne usługi odpłatne prosimy wpłacać na ogólny rachunek bankowy: 88 1020 2906 0000 1302 0661 7932. '
                .'Osoby fizyczne mają indywidualny numer rachunku wirtualnego do płatności za szkolenia indywidualne. Numer i szczegóły przesyłamy w e-mailu potwierdzającym zapis na szkolenie. '
                .'Przed przelewem prosimy sprawdzić, czy wybrano właściwy numer konta, dzięki temu wpłata zostanie prawidłowo zaksięgowana.';
        }

        if ($settings->isDirty()) {
            $settings->save();
            $this->command?->info('Zmieniono teksty kontaktu.');
        } else {
            $this->command?->line('Pominięto kontakt: teksty były już zmieniane.');
        }
    }
}
