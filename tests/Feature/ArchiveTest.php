<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_archived_page_shows_notice_but_stays_reachable(): void
    {
        Page::create(['title' => 'Stary regulamin', 'slug' => 'stary-regulamin', 'type' => 'page', 'is_published' => true, 'is_archived' => true]);

        $this->get('/stary-regulamin')
            ->assertOk()
            ->assertSee('Treść archiwalna')
            ->assertSee('może być już nieaktualna');
    }

    public function test_non_archived_page_has_no_notice(): void
    {
        Page::create(['title' => 'Aktualna', 'slug' => 'aktualna', 'type' => 'page', 'is_published' => true, 'is_archived' => false]);

        $this->get('/aktualna')->assertOk()->assertDontSee('Treść archiwalna');
    }

    public function test_archived_news_is_still_searchable(): void
    {
        News::create(['title' => 'Archiwalny komunikat', 'slug' => 'archiwalny-komunikat', 'is_published' => true, 'is_archived' => true, 'published_at' => now()->subYear(), 'excerpt' => 'stare']);

        // Archiwalne = nadal opublikowane, więc wyszukiwarka je znajduje.
        $this->get(route('search', ['q' => 'Archiwalny']))
            ->assertOk()
            ->assertSee('Archiwalny komunikat');
    }
}
