<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ekrany panelu liczą i zmieniają dane tylko aktywnej witryny (główna albo podrzędna). */
class AdminSiteScopingTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    public function test_czyszczenie_kolorow_menu_dotyczy_tylko_aktywnej_witryny(): void
    {
        $mine = NavItem::withoutGlobalScopes()->create(['site_id' => $this->main->id, 'label' => 'A', 'location' => 'main', 'url' => '/a', 'type' => 'link', 'accent_color' => '#111111']);
        $other = NavItem::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'label' => 'B', 'location' => 'main', 'url' => '/b', 'type' => 'link', 'accent_color' => '#222222']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->withSession(['admin_site_chosen' => true])->post(route('admin.kolory.wyczysc-menu'))->assertRedirect();

        $this->assertNull($mine->fresh()->accent_color);
        $this->assertSame('#222222', $other->fresh()->accent_color, 'pozycje innej witryny zostają');
    }
}
