<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Wyłącza niebieski przycisk „Napisz do nas” (główny przycisk działania, `main_cta`) w kolumnie bocznej działania
 * „Dostępność w praktyce”. Kontakt zostaje w ramce „Masz pytania?” z przyciskiem „Zadaj pytanie”.
 *
 * Idempotentny: zmienia tylko pierwotną wartość (etykieta „Napisz do nas”, adres /kontakt), więc nie nadpisze
 * własnego przycisku ustawionego później w panelu. Przycisk można też wyłączyć w panelu: Działania → edycja →
 * wyczyścić etykietę i adres głównego przycisku.
 *
 *   php artisan db:seed --class=DostepnoscWPraktyceHideMainCtaSeeder --force
 */
class DostepnoscWPraktyceHideMainCtaSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::withoutGlobalScopes()->where('slug', 'dostepnosc-w-praktyce')->first();

        if (! $project) {
            $this->command?->warn('Pominięto: brak działania „Dostępność w praktyce”.');

            return;
        }

        $cta = (array) ($project->main_cta ?? []);

        if ($cta === []) {
            $this->command?->line('Pominięto: główny przycisk jest już wyłączony.');

            return;
        }

        if (($cta['label'] ?? null) !== 'Napisz do nas' || ($cta['url'] ?? null) !== '/kontakt') {
            $this->command?->warn('Pominięto: przycisk był zmieniany w panelu (nie jest to „Napisz do nas” → /kontakt).');

            return;
        }

        $project->main_cta = null;
        $project->save();
        $this->command?->info('Wyłączono przycisk „Napisz do nas” w działaniu „Dostępność w praktyce”.');
    }
}
