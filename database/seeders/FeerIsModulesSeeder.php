<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Wyłącza w witrynie instytucji szkoleniowej (motyw FEER IS) moduły, których ta witryna nie potrzebuje
 * (SiteSetting::FEER_IS_DISABLED_MODULES). Dopisuje je do już wyłączonych, niczego nie włącza. Moduły można potem
 * włączać z powrotem w Ustawienia → Moduły; ponowne uruchomienie seedera wyłączy je znowu.
 *
 *   php artisan db:seed --class=FeerIsModulesSeeder --force
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsModulesSeeder --force   (witryna inna niż „szkolenia”; slug albo nazwa)
 */
class FeerIsModulesSeeder extends Seeder
{
    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }

        $current = (array) ($site->disabled_modules ?? []);
        $merged = array_values(array_unique([...$current, ...SiteSetting::FEER_IS_DISABLED_MODULES]));

        if ($merged === array_values($current)) {
            $this->command?->line('Pominięto: niepotrzebne moduły są już wyłączone.');

            return;
        }

        $site->forceFill(['disabled_modules' => $merged])->save();
        $this->command?->info('Wyłączono niepotrzebne moduły w witrynie „'.($site->site_name ?: $slug).'”: '.implode(', ', array_diff($merged, $current)).'.');
    }
}
