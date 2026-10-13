<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_changing_slug_of_published_page_adds_301_and_flattens_chains(): void
    {
        $page = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'standard', 'is_published' => true]);
        Redirect::create(['from_path' => '/stara-oferta', 'to_url' => '/oferta', 'is_active' => true]);
        Redirect::create(['from_path' => '/oferta-2026', 'to_url' => '/gdzies', 'is_active' => true]);

        $page->update(['slug' => 'oferta-2026']);

        $this->assertDatabaseHas('redirects', ['from_path' => '/oferta', 'to_url' => '/oferta-2026', 'is_active' => true]);
        $this->assertDatabaseHas('redirects', ['from_path' => '/stara-oferta', 'to_url' => '/oferta-2026']);
        $this->assertDatabaseMissing('redirects', ['from_path' => '/oferta-2026']);
        $this->get('/oferta')->assertRedirect('/oferta-2026')->assertStatus(301);
    }

    public function test_news_slug_change_keeps_section_prefix(): void
    {
        $news = News::create(['title' => 'A', 'slug' => 'stary-wpis', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()]);
        $news->update(['slug' => 'nowy-wpis']);

        $this->assertDatabaseHas('redirects', ['from_path' => '/aktualnosci/stary-wpis', 'to_url' => '/aktualnosci/nowy-wpis']);
    }

    public function test_no_redirect_for_drafts_or_when_skipped_in_form(): void
    {
        $draft = Page::create(['title' => 'Szkic', 'slug' => 'szkic', 'type' => 'standard', 'is_published' => false]);
        $draft->update(['slug' => 'szkic-2']);
        $this->assertDatabaseMissing('redirects', ['from_path' => '/szkic']);

        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-a', 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->put(route('admin.podstrony.update', $page), [
                'title' => 'Strona', 'slug' => 'strona-b', 'type' => 'standard', 'is_published' => 1, 'stay' => 1,
                'parent_id' => '', 'project_id' => '', 'order' => 0, 'content' => '<p>x</p>', 'skip_slug_redirect' => 1,
            ])->assertRedirect();
        $this->assertSame('strona-b', $page->fresh()->slug);
        $this->assertDatabaseMissing('redirects', ['from_path' => '/strona-a']);
    }
}
