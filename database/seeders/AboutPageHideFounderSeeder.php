<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Ukrywa sekcję „Słowo od Fundatora” na stronie „O nas” (slug o-nas). Pozostałe ukryte sekcje zostają.
 * Sekcję można przywrócić w panelu (edycja strony, sekcje strony „O nas”).
 *
 *   php artisan db:seed --class=AboutPageHideFounderSeeder --force
 */
class AboutPageHideFounderSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'o-nas')->where('type', 'about')->first();

        if (! $page) {
            $this->command?->warn('Pominięto: brak strony o-nas.');

            return;
        }

        $hidden = $page->about_sections_hidden ?? [];

        if (in_array('founder', $hidden, true)) {
            $this->command?->line('Pominięto: sekcja „Słowo od Fundatora” jest już ukryta.');

            return;
        }

        $page->about_sections_hidden = array_values([...$hidden, 'founder']);
        $page->save();
        $this->command?->info('Ukryto sekcję „Słowo od Fundatora” na stronie o-nas.');
    }
}
