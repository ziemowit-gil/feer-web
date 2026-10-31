<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;

/**
 * Menu główne witryny „szkolenia” (motyw FEER IS): Nasze szkolenia → katalog, Dla instytucji, Akredytacje i jakość,
 * Aktualności, Kontakt. Brakujące strony (katalog szkoleń, akredytacje) powstają jako szkice w nowych typach stron;
 * pozycje menu do stron niepublikowanych zaczną działać po ich publikacji.
 *
 * Nie nadpisuje menu: gdy witryna ma już jakiekolwiek pozycje menu głównego, nic nie robi. Pozycje „Dla instytucji”
 * i „Nasze szkolenia” dostają podmenu automatycznie z opublikowanych podstron wskazanej strony.
 *
 *   php artisan db:seed --class=SzkoleniaMainMenuSeeder --force
 *   SITE_SLUG=test2 php artisan db:seed --class=SzkoleniaMainMenuSeeder --force   (inna witryna sieci niż „szkolenia”)
 */
class SzkoleniaMainMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Witryna docelowa: zmienna środowiskowa SITE_SLUG z wiersza poleceń, domyślnie „szkolenia”.
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';

        $site = SiteSetting::query()->notTrashed()->where('slug', $slug)->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny o slugu „'.$slug.'”.');

            return;
        }

        if (NavItem::withoutGlobalScopes()->where('site_id', $site->id)->where('location', 'main')->exists()) {
            $this->command?->line('Pominięto: witryna ma już menu główne.');

            return;
        }

        $catalog = $this->page($site, 'Katalog szkoleń', 'training_catalog');
        $institutions = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', 'Dla instytucji i firm szkoleniowych')->first();
        $accreditations = $this->page($site, 'Akredytacje i jakość', 'training_accreditations');

        $items = array_filter([
            ['Nasze szkolenia', '/'.$catalog->slug, null],
            $institutions ? ['Dla instytucji', '/'.$institutions->slug, null] : null,
            ['Akredytacje i jakość', '/'.$accreditations->slug, null],
            ['Aktualności', '/aktualnosci', 'news'],
            ['Kontakt', '/kontakt', null],
        ]);

        foreach (array_values($items) as $i => [$label, $url, $module]) {
            NavItem::withoutGlobalScopes()->create([
                'site_id' => $site->id, 'label' => $label, 'url' => $url, 'type' => 'link', 'location' => 'main',
                'module' => $module, 'is_active' => true, 'order' => $i + 1,
            ]);
        }

        $this->command?->info('Szkolenia: zbudowano menu główne ('.count($items).' pozycji).');
    }

    private function page(SiteSetting $site, string $title, string $type): Page
    {
        return Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', $title)->first()
            ?? Page::withoutGlobalScopes()->create([
                'site_id' => $site->id, 'title' => $title, 'slug' => UniqueSlug::page($title), 'type' => $type,
                'content' => '', 'type_data' => [], 'is_published' => false, 'show_in_menu' => true, 'meta_title' => $title,
            ]);
    }
}
