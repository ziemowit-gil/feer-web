<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Słownictwo strony kontaktu zapisane w ustawieniach witryny: „ogólny rachunek” → „główny rachunek” (opis rachunków
 * i notatka przy nich). Idempotentny: zamienia tylko stare brzmienie, więc po ręcznych zmianach w panelu nic nie psuje.
 *
 *   php artisan db:seed --class=ContactWordingSeeder --force
 */
class ContactWordingSeeder extends Seeder
{
    private const MAP = [
        'Rachunek ogólny' => 'Rachunek główny',
        'rachunek ogólny' => 'rachunek główny',
        'ogólny rachunek' => 'główny rachunek',
        'Ogólny rachunek' => 'Główny rachunek',
        'ogólnym rachunku' => 'głównym rachunku',
        'ogólnego rachunku' => 'głównego rachunku',
    ];

    public function run(): void
    {
        $settings = SiteSetting::first();

        if (! $settings) {
            $this->command?->warn('Pominięto: brak ustawień witryny.');

            return;
        }

        $fix = function ($value) use (&$fix) {
            if (is_array($value)) {
                return array_map($fix, $value);
            }

            return is_string($value) ? strtr($value, self::MAP) : $value;
        };

        foreach (['contact_bank_accounts', 'contact_bank_accounts_note'] as $field) {
            $settings->{$field} = $fix($settings->{$field});
        }

        if ($settings->isDirty()) {
            $settings->save();
            $this->command?->info('Zmieniono słownictwo przy rachunkach bankowych.');
        } else {
            $this->command?->line('Pominięto: nic do zmiany.');
        }
    }
}
