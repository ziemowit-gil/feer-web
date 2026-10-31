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

        $labels = NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->orderBy('order')->pluck('label')->all();
        $this->assertSame(['Nasze szkolenia', 'Dla osób', 'Dla instytucji szkoleniowych', 'Dla instytucji', 'Trenerzy', 'Akredytacje i jakość', 'Aktualności', 'Kontakt'], $labels);
        $this->assertNotNull($footer->fresh(), 'stopka zostaje');

        $backups = File::files(storage_path('app/menu-backups'));
        $this->assertCount(1, $backups);
        $this->assertStringContainsString('Stare menu', File::get($backups[0]->getPathname()));

        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_catalog')->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('title', 'Trenerzy')->count());
    }

    public function test_powtorne_uruchomienie_nie_dubluje_pozycji_ani_stron(): void
    {
        $this->run_();
        $this->run_();

        $this->assertSame(8, NavItem::withoutGlobalScopes()->where('site_id', $this->site->id)->where('location', 'main')->count());
        $this->assertSame(1, Page::withoutGlobalScopes()->where('site_id', $this->site->id)->where('type', 'training_accreditations')->count());
    }

    public function test_bez_witryny_nic_nie_robi(): void
    {
        putenv('SITE_SLUG=nie-ma');
        $this->seed(FeerIsMenuSeeder::class);

        $this->assertSame(0, NavItem::withoutGlobalScopes()->count());
    }
}
