<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MegaMenuColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function dropdown(): NavItem
    {
        return NavItem::create(['label' => 'Oferta', 'url' => '/oferta', 'type' => 'dropdown', 'location' => 'main', 'is_mega' => true, 'is_active' => true, 'order' => 1]);
    }

    private function child(NavItem $parent, string $label, array $extra = []): NavItem
    {
        return NavItem::create($extra + ['label' => $label, 'url' => '/'.str($label)->slug(), 'type' => 'link', 'location' => 'main', 'parent_id' => $parent->id, 'is_active' => true, 'order' => NavItem::where('parent_id', $parent->id)->count()]);
    }

    public function test_naglowek_otwiera_kolumne_a_kolejne_pozycje_trafiaja_pod_niego(): void
    {
        $menu = $this->dropdown();
        $this->child($menu, 'Luźny link');
        $this->child($menu, 'Szkolenia', ['is_column_heading' => true, 'url' => '#']);
        $this->child($menu, 'Dla NGO');
        $this->child($menu, 'Dla firm');
        $this->child($menu, 'Wsparcie', ['is_column_heading' => true, 'url' => '/wsparcie-x']);
        $this->child($menu, 'Darowizna');

        $sections = $menu->fresh()->megaSections();

        $this->assertCount(3, $sections);
        $this->assertNull($sections[0]['heading']);
        $this->assertSame(['Luźny link'], array_column($sections[0]['links'], 1));
        $this->assertSame('Szkolenia', $sections[1]['heading']['label']);
        $this->assertNull($sections[1]['heading']['url'], 'Adres „#” oznacza nagłówek bez linku.');
        $this->assertSame(['Dla NGO', 'Dla firm'], array_column($sections[1]['links'], 1));
        $this->assertSame('/wsparcie-x', $sections[2]['heading']['url']);
        $this->assertTrue(NavItem::sectionsAreGrouped($sections));
    }

    public function test_bez_naglowkow_panel_pozostaje_plaska_lista(): void
    {
        $menu = $this->dropdown();
        $this->child($menu, 'Jeden');
        $this->child($menu, 'Dwa');

        $sections = $menu->fresh()->megaSections();

        $this->assertCount(1, $sections);
        $this->assertFalse(NavItem::sectionsAreGrouped($sections));
    }

    public function test_podstrona_z_wlasnymi_podstronami_staje_sie_kolumna(): void
    {
        $root = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true]);
        $team = Page::create(['title' => 'Zespół', 'slug' => 'zespol', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id]);
        Page::create(['title' => 'Zarząd', 'slug' => 'zarzad', 'type' => 'standard', 'is_published' => true, 'parent_id' => $team->id]);
        Page::create(['title' => 'Historia', 'slug' => 'historia-x', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id]);
        Page::create(['title' => 'Szkic', 'slug' => 'szkic-x', 'type' => 'standard', 'is_published' => false, 'parent_id' => $team->id]);

        $menu = NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_mega' => true, 'is_active' => true, 'order' => 1]);

        $sections = $menu->megaSections();

        $this->assertTrue(NavItem::sectionsAreGrouped($sections));
        $this->assertNull($sections[0]['heading']);
        $this->assertSame(['Historia'], array_column($sections[0]['links'], 1));
        $this->assertSame('Zespół', $sections[1]['heading']['label']);
        $this->assertSame(['Zarząd'], array_column($sections[1]['links'], 1));
    }

    public function test_panel_renderuje_listy_opisane_naglowkami_i_bez_landmarku_region(): void
    {
        $menu = $this->dropdown();
        $this->child($menu, 'Szkolenia', ['is_column_heading' => true, 'url' => '#']);
        $this->child($menu, 'Dla NGO');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Szkolenia', $html);
        $this->assertMatchesRegularExpression('/<ul role="list" aria-labelledby="mega-sec-'.$menu->id.'-0"/', $html);
        $this->assertMatchesRegularExpression('/<p id="mega-sec-'.$menu->id.'-0"/', $html);
        $this->assertStringNotContainsString('aria-label="Oferta — podmenu"', $html);
        $this->assertStringContainsString('aria-label="Podmenu: Oferta"', $html);
        $this->assertStringContainsString('lg:grid-cols-2', $html);
    }

    public function test_panel_zapisuje_flage_naglowka_tylko_dla_podpozycji(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $menu = $this->dropdown();
        $child = $this->child($menu, 'Grupa');

        $this->actingAs($admin)->put(route('admin.pozycje-menu.update', $child), [
            'label' => 'Grupa', 'url' => '#', 'type' => 'link', 'location' => 'main', 'parent_id' => $menu->id, 'is_column_heading' => '1', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($child->fresh()->is_column_heading);

        // Pozycja główna nie może być nagłówkiem kolumny.
        $this->actingAs($admin)->put(route('admin.pozycje-menu.update', $menu), [
            'label' => 'Oferta', 'url' => '#', 'type' => 'dropdown', 'location' => 'main', 'is_column_heading' => '1', 'is_active' => '1',
        ]);
        $this->assertFalse($menu->fresh()->is_column_heading);
    }

    public function test_w_zwyklym_dropdownie_naglowek_jest_etykieta_grupy(): void
    {
        $menu = NavItem::create(['label' => 'Zwykłe', 'url' => '#', 'type' => 'dropdown', 'location' => 'main', 'is_mega' => false, 'is_active' => true, 'order' => 1]);
        $this->child($menu, 'Grupa A', ['is_column_heading' => true, 'url' => '#']);
        $this->child($menu, 'Pozycja A1');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<p class="px-4 pb-1 pt-3 text-xs font-bold uppercase[^"]*">Grupa A<\/p>/', $html);
        $this->assertStringContainsString('Pozycja A1', $html);
    }
}
