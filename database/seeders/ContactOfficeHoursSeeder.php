<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Godziny pracy biura na stronie kontaktu: dotychczasowe „Od poniedziałku do środy, 10:00 do 12:00” zmieniają się na
 * godziny 9:00 do 16:00, a przy nich pojawia się informacja, że biurowo obsługuje nas HubKolektyw, a zespół FEER pracuje zdalnie.
 *
 * Idempotentny: godziny są podmieniane tylko przy dawnym brzmieniu, a informacja dopisywana tylko do pustego pola
 * „Uwaga przy adresie biura” (contact_office_note), więc seeder nie nadpisze zmian zrobionych później w panelu.
 *
 *   php artisan db:seed --class=ContactOfficeHoursSeeder --force
 */
class ContactOfficeHoursSeeder extends Seeder
{
    public const OLD_HOURS = 'Od poniedziałku do środy, 10:00 do 12:00';

    public const NEW_HOURS = 'Od poniedziałku do środy, 9:00 do 16:00';

    public const NOTE = 'Biurowo obsługuje nas HubKolektyw, a my pracujemy zdalnie. Wizytę w biurze warto umówić wcześniej.';

    public function run(): void
    {
        $settings = SiteSetting::first();

        if (! $settings) {
            $this->command?->warn('Pominięto: brak ustawień witryny.');

            return;
        }

        if (trim((string) $settings->contact_office_hours) === self::OLD_HOURS) {
            $settings->contact_office_hours = self::NEW_HOURS;
        }

        if (blank($settings->contact_office_note)) {
            $settings->contact_office_note = self::NOTE;
        }

        if ($settings->isDirty()) {
            $settings->save();
            $this->command?->info('Zmieniono godziny pracy biura i informację o obsłudze biurowej.');
        } else {
            $this->command?->line('Pominięto: godziny i informacja były już zmieniane.');
        }
    }
}
