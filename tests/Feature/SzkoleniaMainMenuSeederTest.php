<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Database\Seeders\SzkoleniaInstitutionPagesSeeder;
use Database\Seeders\SzkoleniaMainMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SzkoleniaMainMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'parent_site_id' => $main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is',
        ]);
    }

    public function test_buduje_menu_glowne_i_szkice_stron_nowych_typow_oraz_jest_idempotentne(): void
    {
        $this->seed(SzkoleniaInstitutionPagesSeeder::class);
        $this->seed(SzkoleniaMainMenuSeeder::class);
        $this->seed(SzkoleniaMainMenuSeeder::class);

        $items = NavItem::withoutGlobalScopes()->where('site_id', $this->sub->id)->where('location', 'main')->orderBy('order')->get();
        $this->assertSame(['Nasze szkolenia', 'Dla instytucji', 'Akredytacje i jakość', 'Aktualności', 'Kontakt'], $items->pluck('label')->all());
        $this->assertSame('news', $items->firstWhere('label', 'Aktualności')->module);

        $catalog = Page::withoutGlobalScopes()->where('site_id', $this->sub->id)->where('type', 'training_catalog')->sole();
        $this->assertFalse((bool) $catalog->is_published, 'katalog to szkic do akceptacji');
        $this->assertSame('/'.$catalog->slug, $items->firstWhere('label', 'Nasze szkolenia')->url);
        $this->assertSame(1, Page::withoutGlobalScopes()->where('type', 'training_accreditations')->count());
    }

    public function test_nie_nadpisuje_istniejacego_menu(): void
    {
        NavItem::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'label' => 'Własne', 'url' => '/wlasne', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $this->seed(SzkoleniaMainMenuSeeder::class);

        $this->assertSame(['Własne'], NavItem::withoutGlobalScopes()->where('site_id', $this->sub->id)->where('location', 'main')->pluck('label')->all());
    }

    public function test_menu_na_stronie_ma_linki_z_prefiksem_witryny(): void
    {
        Page::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'title' => 'Katalog szkoleń', 'slug' => 'katalog-szkolen', 'type' => 'training_catalog', 'is_published' => true, 'content' => '', 'type_data' => []]);
        $this->seed(SzkoleniaMainMenuSeeder::class);

        $this->get('/szkolenia')->assertOk()->assertSee('href="/szkolenia/katalog-szkolen"', false)->assertSee('Nasze szkolenia');
    }
}
