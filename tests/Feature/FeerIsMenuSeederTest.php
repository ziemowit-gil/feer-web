<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Database\Seeders\FeerIsMenuSeeder;
use Database\Seeders\SzkoleniaInstitutionPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FeerIsMenuSeederTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $site;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->site = SiteSetting::create([
            'site_name' => 'test2', 'slug' => 'test-dwa', 'parent_site_id' => SiteSetting::current()->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is',
            'services_tabs' => "Dla osób|dla-osob\nDla instytucji szkoleniowych|dla-firm",
        ]);
        File::deleteDirectory(storage_path('app/menu-backups'));
    }

    protected function tearDown(): void
    {
        putenv('SITE_SLUG');
        File::deleteDirectory(storage_path('app/menu-backups'));
        parent::tearDown();
    }

    private function run_(): void
    {
        putenv('SITE_SLUG=test2');
        $this->seed(SzkoleniaInstitutionPagesSeeder::class);
        $this->seed(FeerIsMenuSeeder::class);
    }

    public function test_przebudowuje_istniejace_menu_z_kopia_zapasowa_i_zakladkami_odbiorcow(): void
    {
        NavItem::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'label' => 'Stare menu', 'url' => '/stare', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        $footer = NavItem::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'label' => 'Stopka', 'url' => '/s', 'type' => 'link', 'location' => 'footer', 'is_active' => true, 'order' => 1]);

        $this->run_();

        $labels = NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->whereNull('parent_id')->orderBy('order')->pluck('label')->all();
        $this->assertSame(['Nasze szkolenia', 'Dla osób', 'Dla instytucji szkoleniowych', 'Trenerzy', 'O instytucji', 'Aktualności', 'Kontakt'], $labels);

        $about = Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('title', 'O instytucji szkoleniowej')->sole();
        $this->assertSame('training_institution', $about->type);
        $children = Page::withoutGlobalScopes()->where('parent_id', $about->id)->orderBy('order')->pluck('title')->all();
        $this->assertSame(['Akredytacje i jakość', 'Regulamin szkoleń', 'Cennik szkoleń'], $children);
        $this->assertNotNull($footer->fresh(), 'stopka zostaje');

        $backups = File::files(storage_path('app/menu-backups'));
        $this->assertCount(1, $backups);
        $this->assertStringContainsString('Stare menu', File::get($backups[0]->getPathname()));

        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_catalog')->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('title', 'Trenerzy')->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_accreditations')->count());
    }

    public function test_pod_nasze_szkolenia_sa_podstrony_z_modulow_zajecia_i_wydarzenia(): void
    {
        $this->run_();

        $parent = NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->where('label', 'Nasze szkolenia')->sole();
        $children = NavItem::withoutGlobalScopes()->where('parent_id', $parent->id)->orderBy('order')->get();

        $this->assertSame(['Zajęcia i zapisy', 'Szkolenia i wydarzenia'], $children->pluck('label')->all());
        $this->assertSame(['/zajecia', '/wydarzenia'], $children->pluck('url')->all());
        $this->assertSame(['courses', 'events'], $children->pluck('module')->all());

        $this->site->forceFill(['disabled_modules' => ['courses']])->save();
        NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->delete();
        $this->run_();
        $this->assertSame(['Szkolenia i wydarzenia'], NavItem::withoutGlobalScopes()->where('parent_id', NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('label', 'Nasze szkolenia')->value('id'))->pluck('label')->all(), 'wyłączony moduł nie ma pozycji w menu');
    }

    public function test_powtorne_uruchomienie_nie_dubluje_pozycji_ani_stron(): void
    {
        $this->run_();
        $this->run_();

        $this->assertSame(9, NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->count(), '7 pozycji głównych i 2 podpozycje z modułów, bez dubli');
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_accreditations')->count());
    }

    public function test_bez_witryny_nic_nie_robi(): void
    {
        putenv('SITE_SLUG=nie-ma');
        $this->seed(FeerIsMenuSeeder::class);

        $this->assertSame(0, NavItem::withoutGlobalScopes()->count());
    }

    public function test_pozycje_z_podstronami_to_mega_menu_a_menu_jest_kompaktowe(): void
    {
        $this->run_();
        $catalog = Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_catalog')->sole();
        $catalog->forceFill(['is_published' => true])->save();
        Page::withoutGlobalScopes()->create(['site_id' => $this->site->id, 'parent_id' => $catalog->id, 'title' => 'Szkolenie testowe', 'slug' => 'szkolenie-testowe', 'type' => 'training_offer', 'is_published' => true, 'content' => '', 'type_data' => []]);

        $items = NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->get()->keyBy('label');
        $this->assertTrue((bool) $items['Nasze szkolenia']->is_mega);
        $this->assertSame('sm', $items['Nasze szkolenia']->mega_size);
        $this->assertFalse((bool) $items['Kontakt']->is_mega);

        $html = $this->get('/test-dwa')->assertOk()->getContent();
        $this->assertStringContainsString('text-sm font-bold uppercase tracking-wide xl:gap-5', $html, 'kompaktowe menu IS');
        $this->assertStringNotContainsString('text-lg font-bold uppercase tracking-wide xl:gap-6', $html);
        $this->assertStringContainsString('Szkolenie testowe', $html, 'oferta w panelu mega menu');
    }
}
