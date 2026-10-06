<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SideNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function parentWithChild(bool $sideNav): Page
    {
        $parent = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'show_side_nav' => $sideNav]);
        Page::create(['title' => 'Podstrona A', 'slug' => 'podstrona-a', 'type' => 'standard', 'is_published' => true, 'parent_id' => $parent->id, 'show_side_nav' => $sideNav]);

        return $parent;
    }

    public function test_side_nav_shows_children_when_enabled(): void
    {
        $this->parentWithChild(true);

        $this->get('/dzial')
            ->assertOk()
            ->assertSee('Podstrony w tym dziale')
            ->assertSee('Podstrona A');
    }

    public function test_side_nav_hidden_when_disabled(): void
    {
        $this->parentWithChild(false);

        $this->get('/dzial')
            ->assertOk()
            ->assertDontSee('Podstrony w tym dziale');
    }

    public function test_tree_style_renders_whole_section_with_rootline(): void
    {
        $root = Page::create(['title' => 'Dział główny', 'slug' => 'dzial-glowny', 'type' => 'standard', 'is_published' => true, 'side_nav_style' => 'tree']);
        $child = Page::create(['title' => 'Poziom drugi', 'slug' => 'poziom-drugi', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id]);
        Page::create(['title' => 'Poziom trzeci', 'slug' => 'poziom-trzeci', 'type' => 'standard', 'is_published' => true, 'parent_id' => $child->id]);
        Page::create(['title' => 'Inna gałąź', 'slug' => 'inna-galaz', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id]);
        Page::create(['title' => 'Szkic ukryty', 'slug' => 'szkic-ukryty', 'type' => 'standard', 'is_published' => false, 'parent_id' => $root->id]);

        $response = $this->get('/poziom-trzeci')->assertOk();

        // Drzewo od korzenia działu: wszystkie poziomy, bez szkiców, bieżąca strona oznaczona.
        $response->assertSee('Jesteś tu:')
            ->assertSee('Dział główny')
            ->assertSee('Poziom drugi')
            ->assertSee('Inna gałąź')
            ->assertDontSee('Szkic ukryty')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Zwiń: Poziom drugi', false)
            ->assertSee('md:grid-cols-[260px_1fr]', false);

        // Okruszki pokazują pełną ścieżkę działu, nie tylko bezpośredniego rodzica.
        $this->assertSame([$root->id, $child->id], Page::where('slug', 'poziom-trzeci')->first()->ancestors()->pluck('id')->all());
    }

    public function test_unknown_style_falls_back_to_sidebar(): void
    {
        $parent = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'side_nav_style' => 'bogus']);
        $child = Page::create(['title' => 'Podstrona A', 'slug' => 'podstrona-a', 'type' => 'standard', 'is_published' => true, 'parent_id' => $parent->id]);

        $this->assertSame('sidebar', $parent->sideNavStyle());
        $this->assertSame('sidebar', $child->sideNavStyle());
    }
}
