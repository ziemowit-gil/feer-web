<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * PRZEBUDOWUJE menu główne witryny w motywie FEER IS (instytucja szkoleniowa), także gdy witryna ma już menu
 * (SzkoleniaMainMenuSeeder wtedy nic nie robi). Stare pozycje menu głównego są usuwane, a ich kopia zapisywana w
 * storage/app/menu-backups/<witryna>-<data>.json (do ręcznego przywrócenia). Stopki i inne menu zostają bez zmian.
 *
 * Nowe menu (mega menu z kolumnami z podstron wskazanych stron; w motywie FEER IS menu jest kompaktowe, w jednym rzędzie):
 * Nasze szkolenia (katalog, podmenu z opublikowanych szkoleń), po jednej pozycji na zakładkę odbiorców
 * z ustawień FEER IS, Dla instytucji, Trenerzy, O instytucji (podstrony: Akredytacje i jakość, Regulamin szkoleń, Cennik szkoleń), Aktualności, Kontakt. Brakujące strony
 * (katalog, trenerzy, akredytacje) powstają jako szkice; pozycje do stron niepublikowanych działają po publikacji.
 *
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsMenuSeeder --force   (slug albo nazwa witryny; domyślnie „szkolenia”)
 */
class FeerIsMenuSeeder extends Seeder
{
    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }

        $old = NavItem::withoutGlobalScopes()->where('site_id', $site->id)->where('location', 'main')->get();

        if ($old->isNotEmpty()) {
            $dir = storage_path('app/menu-backups');
            File::ensureDirectoryExists($dir);
            $file = $dir.'/'.str($site->slug ?: $site->site_name)->slug().'-'.now()->format('Ymd-His').'.json';
            File::put($file, $old->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            NavItem::withoutGlobalScopes()->where('site_id', $site->id)->where('location', 'main')->delete();
            $this->command?->line('Usunięto '.$old->count().' starych pozycji menu głównego; kopia: '.$file);
        }

        $catalog = $this->page($site, 'Katalog szkoleń', 'training_catalog');
        $trainers = $this->page($site, 'Trenerzy', 'standard', '<p>[podstrony]</p>');

        // „O instytucji szkoleniowej” (typ „Instytucja szkoleniowa”; dane RIS i BUR uzupełnia redakcja) z podstronami:
        // akredytacje i jakość, regulamin szkoleń, cennik. Podstrony trafiają pod tę stronę tylko wtedy, gdy nie mają rodzica.
        $about = $this->page($site, 'O instytucji szkoleniowej', 'training_institution', '<p>[podstrony]</p>');
        foreach ([['Akredytacje i jakość', 'training_accreditations'], ['Regulamin szkoleń', 'regulation'], ['Cennik szkoleń', 'pricing']] as $order => [$title, $type]) {
            $child = $this->page($site, $title, $type);
            if (! $child->parent_id) {
                $child->forceFill(['parent_id' => $about->id, 'order' => $order + 1])->save();
            }
        }
        $institutions = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', 'Dla instytucji i firm szkoleniowych')->first();

        // Trzeci element: moduł menu, czwarty: mega menu (kolumny z podstron wskazanej strony), piąty: opis karty bocznej.
        $items = [['Nasze szkolenia', '/'.$catalog->slug, null, true, 'Szkolenia, terminy i zapisy w jednym miejscu.']];

        foreach ($site->servicesTabs() as $tab) {
            $items[] = [$tab['label'], '/'.$tab['slug'], null, true, null];
        }
        if ($institutions) {
            $items[] = ['Dla instytucji', '/'.$institutions->slug, null, true, 'Szkolenia i współpraca dla firm i instytucji.'];
        }
        array_push($items, ['Trenerzy', '/'.$trainers->slug, null, true, null], ['O instytucji', '/'.$about->slug, null, true, 'Kim jesteśmy, akredytacje, regulamin i cennik szkoleń.'], ['Aktualności', '/aktualnosci', 'news', false, null], ['Kontakt', '/kontakt', null, false, null]);

        foreach ($items as $i => [$label, $url, $module, $mega, $description]) {
            NavItem::withoutGlobalScopes()->create([
                'site_id' => $site->id, 'label' => $label, 'url' => $url, 'type' => 'link', 'location' => 'main',
                'module' => $module, 'is_active' => true, 'order' => $i + 1,
                'is_mega' => $mega, 'mega_size' => $mega ? 'sm' : 'md', 'description' => $description,
            ]);
        }

        $this->command?->info('Zbudowano menu główne witryny „'.($site->site_name ?: $slug).'” ('.count($items).' pozycji): '.collect($items)->pluck(0)->implode(', ').'.');
    }

    private function page(SiteSetting $site, string $title, string $type, string $content = ''): Page
    {
        return Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', $title)->first()
            ?? Page::withoutGlobalScopes()->create([
                'site_id' => $site->id, 'title' => $title, 'slug' => UniqueSlug::page($title), 'type' => $type,
                'content' => $content, 'type_data' => [], 'is_published' => false, 'show_in_menu' => true, 'meta_title' => $title,
            ]);
    }
}
