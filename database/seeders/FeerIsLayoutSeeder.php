<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Układ portalu instytucji szkoleniowo-zajęciowej w motywie FEER IS: szablon FEER z wariantem FEER IS, zakładki
 * odbiorców (Dla osób, Dla instytucji) wskazujące strony z usługami, przycisk w nagłówku i przycisk pod siatką usług
 * prowadzące do katalogu szkoleń. Wartości ustawia tylko tam, gdzie pole jest puste, więc nie nadpisuje zmian z panelu.
 * Zakładki i przyciski wymagają stron z FeerIsPortalContentSeeder (uruchom go wcześniej).
 *
 *   SITE_SLUG=test2 php artisan db:seed --class=FeerIsLayoutSeeder --force   (slug albo nazwa witryny; domyślnie „szkolenia”)
 */
class FeerIsLayoutSeeder extends Seeder
{
    /** Gdy sekcje strony głównej były już ustawiane (lista niepusta), decyzję o aktualnościach zostawiamy redakcji. */
    private function newsAlreadyDecided(SiteSetting $site): bool
    {
        return ! empty($site->homepage_sections_hidden);
    }

    public function run(): void
    {
        $slug = trim((string) (getenv('SITE_SLUG') ?: ($_SERVER['SITE_SLUG'] ?? ''))) ?: 'szkolenia';
        $site = SiteSetting::query()->notTrashed()->where(fn ($q) => $q->where('slug', $slug)->orWhere('site_name', $slug))->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny „'.$slug.'”.');

            return;
        }

        $catalog = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('type', 'training_catalog')->orderBy('id')->first();
        $tabs = [];
        foreach ([['Dla osób', 'mi-person'], ['Dla instytucji', 'mi-apartment']] as [$title, $icon]) {
            if ($page = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', $title)->first()) {
                $tabs[] = $title.'|'.$page->slug.'|'.$icon;
            }
        }

        $values = array_filter([
            'site_template' => 'feer',
            'theme_variant' => 'feer_is',
            'hero_single' => false,
            'services_tabs' => $tabs ? implode("\n", $tabs) : null,
            'services_all_label' => 'Zobacz wszystkie szkolenia',
            'services_all_url' => $catalog ? '/'.$catalog->slug : null,
            'is_cta_label' => 'Zobacz szkolenia',
            'is_cta_url' => $catalog ? '/'.$catalog->slug : null,
        ], fn ($v) => $v !== null);

        // Zapisy online na szkolenia na start wyłączone (włączenie: Ustawienia → Szablon → FEER IS); tylko gdy nikt tego nie ustawił.
        if ($site->getAttribute('courses_registration_enabled') === null && \Illuminate\Support\Facades\Schema::hasColumn('site_settings', 'courses_registration_enabled')) {
            $values['courses_registration_enabled'] = false;
        }

        $changed = [];
        foreach ($values as $column => $value) {
            $current = $site->getAttribute($column);
            // Szablon i wariant ustawiamy zawsze (to cel seedera); pozostałe pola tylko gdy puste.
            if (in_array($column, ['site_template', 'theme_variant', 'hero_single'], true) ? $current !== $value : blank($current)) {
                $site->setAttribute($column, $value);
                $changed[] = $column;
            }
        }

        // Strona główna portalu szkoleniowego nie pokazuje bloku aktualności (można go włączyć w Ustawienia → Strona główna).
        $hidden = (array) ($site->homepage_sections_hidden ?? []);
        if (! in_array('news', $hidden, true) && ! $this->newsAlreadyDecided($site)) {
            $site->homepage_sections_hidden = array_values([...$hidden, 'news']);
            $changed[] = 'homepage_sections_hidden';
        }

        if ($changed) {
            $site->save();
        }

        $this->command?->info($changed
            ? 'Układ FEER IS: ustawiono '.implode(', ', $changed).'.'
            : 'Układ FEER IS: bez zmian (pola są już ustawione).');
    }
}
