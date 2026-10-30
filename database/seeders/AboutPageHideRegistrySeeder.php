<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Wyłącza blok „Dane organizacji” (KRS, NIP, konto) w sekcji „Dokumenty i sprawozdania” na stronie „O nas”
 * (slug o-nas). Pozostałe ustawienia układu strony zostają. Włączysz go z powrotem w panelu (edycja strony „O nas”).
 *
 *   php artisan db:seed --class=AboutPageHideRegistrySeeder --force
 */
class AboutPageHideRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'o-nas')->where('type', 'about')->first();

        if (! $page) {
            $this->command?->warn('Pominięto: brak strony o-nas.');

            return;
        }

        $layout = $page->layout_data ?? [];

        if (($layout['about_registry'] ?? true) === false || ($layout['about_registry'] ?? true) === '0' || ($layout['about_registry'] ?? true) === 0) {
            $this->command?->line('Pominięto: dane organizacji są już wyłączone.');

            return;
        }

        $layout['about_registry'] = false;
        $page->layout_data = $layout;
        $page->save();
        $this->command?->info('Wyłączono dane organizacji w sekcji dokumentów na stronie o-nas.');
    }
}
