<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Blog „Wiem FEER” wyłączony: dawne adresy prowadzą na Substack, a strona główna FEER pokazuje wpisy z Substacka. */
class BlogMovedToSubstackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertFalse(app(ModuleManager::class)->isActive('blog'), 'test zakłada wyłączony moduł bloga');
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Cache::flush();
    }

    private function settings(array $data): void
    {
        SiteSetting::current()->forceFill($data)->save();
        $this->reset();
    }

    public function test_dawne_adresy_bloga_prowadza_na_substack(): void
    {
        $this->settings(['substack_url' => 'https://fundacjafeer.substack.com']);

        foreach (['/wiem', '/wiem/jak-pisac-alt', '/blog', '/wiem-feer/stary-wpis'] as $url) {
            $this->get($url)->assertStatus(301)->assertRedirect('https://fundacjafeer.substack.com');
        }
    }

    public function test_bez_adresu_substacka_dawne_adresy_prowadza_do_aktualnosci(): void
    {
        $this->settings(['substack_url' => null]);

        $this->get('/wiem')->assertStatus(302)->assertRedirect(route('news.index'));
    }

    public function test_strona_glowna_feer_pokazuje_wpisy_z_substacka(): void
    {
        Http::fake(['fundacjafeer.substack.com/feed' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>FEER</title>'
            .'<item><title>Dostępność zaczyna się od empatii</title><link>https://fundacjafeer.substack.com/p/empatia</link><pubDate>Thu, 15 Oct 2026 10:00:00 GMT</pubDate><description>Krótko o tym, co robimy.</description></item>'
            .'</channel></rss>', 200, ['Content-Type' => 'application/rss+xml'])]);
        $this->settings(['site_template' => 'feer', 'substack_url' => 'https://fundacjafeer.substack.com']);

        $this->get('/')->assertOk()
            ->assertSee('O tym piszemy')
            ->assertSee('Dostępność zaczyna się od empatii')
            ->assertSee('https://fundacjafeer.substack.com/p/empatia', false)
            ->assertSee('15 października 2026');
    }

    public function test_bez_adresu_substacka_strona_glowna_feer_bez_sekcji(): void
    {
        $this->settings(['site_template' => 'feer', 'substack_url' => null]);

        $this->get('/')->assertOk()->assertDontSee('O tym piszemy');
    }
}
