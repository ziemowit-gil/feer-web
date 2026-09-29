<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSectionNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function section(): array
    {
        $parent = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'is_published' => true, 'content' => '<p>Wstęp działu</p>']);
        $a = Page::create(['title' => 'Pierwsza', 'slug' => 'pierwsza', 'parent_id' => $parent->id, 'is_published' => true, 'order' => 1, 'meta_description' => 'Opis pierwszej']);
        $b = Page::create(['title' => 'Druga', 'slug' => 'druga', 'parent_id' => $parent->id, 'is_published' => true, 'order' => 2]);
        $c = Page::create(['title' => 'Trzecia', 'slug' => 'trzecia', 'parent_id' => $parent->id, 'is_published' => true, 'order' => 3]);
        Page::create(['title' => 'Szkic', 'slug' => 'szkic', 'parent_id' => $parent->id, 'is_published' => false, 'order' => 4]);

        return [$parent, $a, $b, $c];
    }

    public function test_section_page_lists_published_children_as_cards(): void
    {
        $this->section();

        $this->get('/dzial')->assertOk()
            ->assertSee('W tym dziale')
            ->assertSee('Pierwsza')
            ->assertSee('Opis pierwszej')
            ->assertSee('Trzecia')
            ->assertDontSee('Szkic');
    }

    public function test_subpage_shows_previous_and_next_siblings(): void
    {
        $this->section();

        $html = $this->get('/druga')->assertOk()
            ->assertSee('Nawigacja w dziale')
            ->assertSee('Poprzednia')
            ->assertSee('Następna')
            ->assertSee('rel="prev"', false)
            ->assertSee('rel="next"', false)
            ->getContent();

        $this->assertStringContainsString('href="' . url('/pierwsza') . '" rel="prev"', $html);
        $this->assertStringContainsString('href="' . url('/trzecia') . '" rel="next"', $html);
    }

    public function test_first_subpage_has_no_previous_link(): void
    {
        $this->section();

        $this->get('/pierwsza')->assertOk()
            ->assertDontSee('rel="prev"', false)
            ->assertSee('rel="next"', false);
    }

    public function test_table_of_contents_appears_for_three_headings(): void
    {
        Page::create([
            'title' => 'Długa strona', 'slug' => 'dluga', 'is_published' => true,
            'content' => '<h2>Pierwsza sekcja</h2><p>a</p><h2>Druga sekcja</h2><p>b</p><h3>Podsekcja</h3><p>c</p>',
        ]);

        $this->get('/dluga')->assertOk()
            ->assertSee('Na tej stronie')
            ->assertSee('aria-label="Spis treści"', false)
            ->assertSee('href="#pierwsza-sekcja"', false)
            ->assertSee('<h2 id="pierwsza-sekcja">', false)
            ->assertSee('href="#podsekcja"', false);
    }

    public function test_no_table_of_contents_for_short_pages(): void
    {
        Page::create(['title' => 'Krótka', 'slug' => 'krotka', 'is_published' => true, 'content' => '<h2>Jedna</h2><p>a</p>']);

        $this->get('/krotka')->assertOk()->assertDontSee('Na tej stronie');
    }
}
