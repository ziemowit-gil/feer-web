<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_search_finds_pages_and_news(): void
    {
        Page::create(['title' => 'Oferta specjalna', 'slug' => 'oferta-specjalna', 'type' => 'page', 'is_published' => true, 'is_disabled' => false, 'content' => 'Treść o dostępności']);
        News::create(['title' => 'Nowa oferta warsztatów', 'slug' => 'nowa-oferta', 'is_published' => true, 'published_at' => now()->subDay(), 'excerpt' => 'Zapraszamy']);

        $this->get(route('search', ['q' => 'oferta']))
            ->assertOk()
            ->assertSee('Oferta specjalna')
            ->assertSee('Nowa oferta warsztatów')
            ->assertSee('Strony')
            ->assertSee('Aktualności');
    }

    public function test_empty_and_short_queries_show_prompt(): void
    {
        $this->get(route('search'))->assertOk()->assertSee('Wpisz frazę');
        $this->get(route('search', ['q' => 'a']))->assertOk()->assertSee('Wpisz frazę');
    }

    public function test_unpublished_content_is_not_found(): void
    {
        Page::create(['title' => 'Tajna strona', 'slug' => 'tajna', 'type' => 'page', 'is_published' => false]);

        $this->get(route('search', ['q' => 'Tajna']))
            ->assertOk()
            ->assertSee('Brak wyników')
            ->assertDontSee('Tajna strona');
    }
}
