<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pozycje menu: „M” przy stronach z modułów, puste przy stronach CMS, stronie głównej i linkach zewnętrznych. */
class NavItemModuleMarkerTest extends TestCase
{
    use RefreshDatabase;

    private function item(array $a): NavItem
    {
        return NavItem::create($a + ['label' => 'Pozycja', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
    }

    public function test_rozpoznaje_strony_modulow(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'content' => '<p>x</p>', 'is_published' => true]);

        $this->assertTrue($this->item(['url' => '/materialy'])->isModuleLink());
        $this->assertTrue($this->item(['url' => '/aktualnosci'])->isModuleLink());
        $this->assertTrue($this->item(['type' => 'events', 'url' => null])->isModuleLink());
        $this->assertFalse($this->item(['url' => '/o-nas'])->isModuleLink());
        $this->assertFalse($this->item(['url' => '/'])->isModuleLink());
        $this->assertFalse($this->item(['url' => 'https://example.org/x'])->isModuleLink());
        $this->assertFalse($this->item(['url' => '#kontakt'])->isModuleLink());
    }

    public function test_drzewo_menu_pokazuje_m(): void
    {
        $this->item(['label' => 'Materiały', 'url' => '/materialy']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.pozycje-menu.index', ['location' => 'main']))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~title="Strona z modułu"\s*>M<span class="sr-only"> — strona z modułu</span>~u', $html);
    }
}
