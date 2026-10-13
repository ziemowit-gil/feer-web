<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\PjmVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PjmVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_parses_youtube_vimeo_and_files(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&cc_load_policy=1', PjmVideo::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10')['src']);
        $this->assertSame('youtube', PjmVideo::parse('https://youtu.be/dQw4w9WgXcQ')['type']);
        $this->assertSame('youtube', PjmVideo::parse('https://youtube.com/shorts/dQw4w9WgXcQ')['type']);
        $this->assertSame('https://player.vimeo.com/video/76979871?dnt=1', PjmVideo::parse('https://vimeo.com/76979871')['src']);
        $this->assertSame('file', PjmVideo::parse('https://cdn.example.org/pjm/strona.mp4')['type']);

        $this->assertNull(PjmVideo::parse('http://cdn.example.org/pjm/strona.mp4')); // bez https
        $this->assertNull(PjmVideo::parse('https://example.org/strona'));
        $this->assertNull(PjmVideo::parse('javascript:alert(1)'));
        $this->assertNull(PjmVideo::parse(''));
    }

    public function test_admin_saves_and_clears_pjm_video_for_news(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $news = News::create(['title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subDay()]);

        $this->actingAs($admin)->put(route('admin.pjm.update', ['type' => 'news', 'id' => $news->id]), ['pjm_video_url' => 'https://example.org/strona'])
            ->assertSessionHasErrors('pjm_video_url');

        $this->actingAs($admin)->put(route('admin.pjm.update', ['type' => 'news', 'id' => $news->id]), ['pjm_video_url' => 'https://youtu.be/dQw4w9WgXcQ'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $news->fresh()->pjm_video_url);

        $this->get(route('news.show', $news))->assertOk()
            ->assertSee('Tłumaczenie na PJM')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        $this->actingAs($admin)->put(route('admin.pjm.update', ['type' => 'news', 'id' => $news->id]), ['pjm_video_url' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($news->fresh()->pjm_video_url);
    }

    public function test_page_shows_pjm_button_only_when_set(): void
    {
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Treść</p>']);

        $this->get('/o-nas')->assertOk()->assertDontSee('Tłumaczenie na PJM');

        $page->forceFill(['pjm_video_url' => 'https://vimeo.com/76979871'])->save();
        $this->get('/o-nas')->assertOk()
            ->assertSee('Tłumaczenie na PJM')
            ->assertSee('player.vimeo.com/video/76979871', false);
    }

    public function test_unknown_type_is_404_and_editors_need_panel_access(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->put(route('admin.pjm.update', ['type' => 'uzytkownik', 'id' => 1]), ['pjm_video_url' => ''])
            ->assertNotFound();
    }
}
