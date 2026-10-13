<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Content\PageGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageGraphTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_graph_has_parent_and_link_edges_and_warnings(): void
    {
        $about = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => true]);
        $team = Page::create(['title' => 'Zespół', 'slug' => 'zespol', 'type' => 'standard', 'is_published' => true, 'parent_id' => $about->id,
            'content' => '<p><a href="/oferta-szkic">Oferta</a> i <a href="'.url('/o-nas').'">o nas</a> oraz <a href="https://example.com/o-nas">obcy</a></p>']);
        $draft = Page::create(['title' => 'Oferta szkic', 'slug' => 'oferta-szkic', 'type' => 'standard', 'is_published' => false, 'show_in_menu' => false]);
        $lonely = Page::create(['title' => 'Samotna', 'slug' => 'samotna', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => false]);

        $g = app(PageGraph::class)->build();
        $edges = collect($g['edges'])->pluck('data');

        $this->assertTrue($edges->contains(fn ($e) => $e['kind'] === 'parent' && $e['source'] === 'p'.$about->id && $e['target'] === 'p'.$team->id));
        $this->assertTrue($edges->contains(fn ($e) => $e['kind'] === 'link' && $e['source'] === 'p'.$team->id && $e['target'] === 'p'.$draft->id));
        $this->assertTrue($edges->contains(fn ($e) => $e['kind'] === 'link' && $e['target'] === 'p'.$about->id));
        $this->assertSame(2, $edges->where('kind', 'link')->count(), 'Link zewnętrzny nie jest zależnością.');
        $orphans = array_column($g['warnings']['orphans'], 'title');
        $this->assertContains('Samotna', $orphans);
        $this->assertNotContains('O nas', $orphans);
        $this->assertNotContains('Zespół', $orphans);
        $this->assertSame([['from' => 'Zespół', 'from_id' => $team->id, 'to' => 'Oferta szkic']], $g['warnings']['draft_links']);
    }

    public function test_graph_page_renders_with_text_alternative(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'show_in_menu' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get(route('admin.podstrony.mapa'))
            ->assertOk()
            ->assertSee('Mapa zależności stron')
            ->assertSee('Zależności w tabeli')
            ->assertSee('cytoscape', false)
            ->assertSee('O nas');
    }
}
