<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\User;
use App\Services\Content\PageGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mapa zależności: strony z menu głównego są stronami głównymi (wychodzą od węzła „Menu główne”). */
class PageGraphMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_strony_z_menu_sa_glowne_i_wychodza_od_menu(): void
    {
        NavItem::query()->delete();
        $kontakt = Page::create(['title' => 'Kontakt testowy', 'slug' => 'kontakt-testowy', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => false]);
        $inna = Page::create(['title' => 'Inna testowa', 'slug' => 'inna-testowa', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => false]);
        NavItem::create(['label' => 'Kontakt', 'url' => '/kontakt-testowy', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 0]);

        $graph = app(PageGraph::class)->build();
        $nodes = collect($graph['nodes'])->keyBy('data.id');

        $this->assertTrue($nodes['p'.$kontakt->id]['data']['main']);
        $this->assertFalse($nodes['p'.$inna->id]['data']['main']);
        $this->assertSame('menu', $nodes['menu']['data']['kind']);
        $this->assertTrue(collect($graph['edges'])->contains(fn ($e) => $e['data']['kind'] === 'menu' && $e['data']['target'] === 'p'.$kontakt->id));
        $this->assertFalse(collect($graph['rows'])->firstWhere('id', $kontakt->id)['orphan']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.podstrony.mapa'))->assertOk()->assertSee('Menu główne');
    }

    public function test_wezly_maja_dane_do_drzewa_bocznego(): void
    {
        NavItem::query()->delete();
        $druga = Page::create(['title' => 'Druga w menu', 'slug' => 'druga-w-menu', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => false]);
        $pierwsza = Page::create(['title' => 'Pierwsza w menu', 'slug' => 'pierwsza-w-menu', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => false]);
        $pod = Page::create(['title' => 'Podstrona testowa', 'slug' => 'podstrona-testowa', 'type' => 'standard', 'is_published' => false, 'parent_id' => $pierwsza->id]);
        NavItem::create(['label' => 'Pierwsza', 'url' => '/pierwsza-w-menu', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 0]);
        NavItem::create(['label' => 'Druga', 'url' => '/druga-w-menu', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $nodes = collect(app(PageGraph::class)->build()['nodes'])->keyBy('data.id');

        // Kolejność stron głównych jak w menu, nie jak w bazie.
        $this->assertSame(0, $nodes['p'.$pierwsza->id]['data']['menu_order']);
        $this->assertSame(1, $nodes['p'.$druga->id]['data']['menu_order']);
        $this->assertNull($nodes['p'.$pod->id]['data']['menu_order']);
        $this->assertSame('p'.$pierwsza->id, $nodes['p'.$pod->id]['data']['parent']);
        $this->assertNull($nodes['p'.$pierwsza->id]['data']['parent']);
        $this->assertNull($nodes['p'.$pod->id]['data']['project']);
        $this->assertSame(route('admin.podstrony.index', ['wybrana' => $pod->id]), $nodes['p'.$pod->id]['data']['tree']);
    }
}
