<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_feed_contains_only_published_news(): void
    {
        News::create(['title' => 'Opublikowana', 'slug' => 'opublikowana', 'is_published' => true, 'published_at' => now()->subDay(), 'excerpt' => 'jawna']);
        News::create(['title' => 'Szkic', 'slug' => 'szkic', 'is_published' => false, 'published_at' => now()->subDay(), 'excerpt' => 'ukryta']);
        News::create(['title' => 'Zaplanowana', 'slug' => 'zaplanowana', 'is_published' => true, 'published_at' => now()->addWeek(), 'excerpt' => 'jeszcze nie']);

        $response = $this->get('/rss.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('Opublikowana')
            ->assertDontSee('Szkic')
            ->assertDontSee('Zaplanowana');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'Kanał RSS nie jest poprawnym XML-em.');
        $this->assertCount(1, $xml->channel->item);
        $this->assertSame(url('/aktualnosci/opublikowana'), (string) $xml->channel->item[0]->link);
    }

    public function test_feed_can_be_filtered_by_category(): void
    {
        $category = NewsCategory::create(['name' => 'Dostępność', 'slug' => 'dostepnosc']);

        News::create(['title' => 'W kategorii', 'slug' => 'w-kategorii', 'news_category_id' => $category->id, 'is_published' => true, 'published_at' => now()->subDay()]);
        News::create(['title' => 'Bez kategorii', 'slug' => 'bez-kategorii', 'is_published' => true, 'published_at' => now()->subDay()]);

        $this->get('/aktualnosci/rss.xml?kategoria=dostepnosc')
            ->assertOk()
            ->assertSee('W kategorii')
            ->assertDontSee('Bez kategorii');
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->get('/aktualnosci/rss.xml?kategoria=nie-ma-takiej')->assertNotFound();
    }

    public function test_feed_route_does_not_shadow_news_detail(): void
    {
        News::create(['title' => 'Artykuł', 'slug' => 'artykul', 'is_published' => true, 'published_at' => now()->subDay()]);

        $this->get('/aktualnosci/artykul')->assertOk()->assertSee('Artykuł');
    }

    public function test_pages_advertise_the_feed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('type="application/rss+xml"', false)
            ->assertSee(route('feed'), false);
    }
}
