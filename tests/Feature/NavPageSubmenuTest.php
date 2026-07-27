<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavPageSubmenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function navLink(string $url, array $overrides = []): NavItem
    {
        return NavItem::create(array_merge([
            'label' => 'Oferta',
            'url' => $url,
            'type' => 'link',
            'location' => 'main',
            'is_active' => true,
            'order' => 0,
        ], $overrides));
    }

    public function test_linked_page_resolves_for_internal_page_link(): void
    {
        $page = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'page', 'is_published' => true]);

        $this->assertSame($page->id, $this->navLink('/oferta')->linkedPage()?->id);
        $this->assertSame($page->id, $this->navLink('oferta')->linkedPage()?->id);
        $this->assertSame($page->id, $this->navLink(url('/oferta'))->linkedPage()?->id);
    }

    public function test_no_linked_page_for_external_button_or_missing(): void
    {
        $this->assertNull($this->navLink('https://example.com')->linkedPage());
        $this->assertNull($this->navLink('#kontakt')->linkedPage());
        $this->assertNull($this->navLink('/oferta', ['is_button' => true])->linkedPage());
        $this->assertNull($this->navLink('/nie-ma-takiej')->linkedPage());
    }

    public function test_child_pages_render_as_submenu_on_a_page(): void
    {
        $parent = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'page', 'is_published' => true]);
        Page::create(['title' => 'Szkolenia WCAG', 'slug' => 'szkolenia-wcag', 'type' => 'page', 'is_published' => true, 'parent_id' => $parent->id]);
        $this->navLink('/oferta');

        $this->get('/oferta')
            ->assertOk()
            ->assertSee('Szkolenia WCAG');
    }

    public function test_unpublished_children_are_not_shown(): void
    {
        $parent = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'page', 'is_published' => true]);
        Page::create(['title' => 'Ukryta podstrona', 'slug' => 'ukryta', 'type' => 'page', 'is_published' => false, 'parent_id' => $parent->id]);

        $this->assertTrue($this->navLink('/oferta')->linkedPage()->publishedChildren->isEmpty());
    }
}
