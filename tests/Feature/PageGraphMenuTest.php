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
}
