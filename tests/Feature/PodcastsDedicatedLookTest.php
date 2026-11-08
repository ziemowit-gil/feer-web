<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dedykowany wygląd podstrony /podcasty niezależny od szablonu całej witryny (Ustawienia → Szablon). */
class PodcastsDedicatedLookTest extends TestCase
{
    use RefreshDatabase;

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function useSettings(array $attrs): void
    {
        $this->reset();
        SiteSetting::current()->update($attrs);
        $this->reset();
    }

    public function test_bez_ustawienia_podstrona_ma_szablon_witryny(): void
    {
        $this->useSettings(['site_template' => 'default', 'podcasts_template' => null]);
        $html = $this->get('/podcasty')->assertOk()->getContent();
        $this->assertStringNotContainsString('bg-ink text-white', substr($html, (int) strpos($html, '<footer')), 'szablon domyślny nie ma stopki FEER');
    }

    public function test_ustawiony_wyglad_feer_tylko_dla_podcastow(): void
    {
        $this->useSettings(['site_template' => 'default', 'podcasts_template' => 'feer']);
        $podcasty = $this->get('/podcasty')->assertOk()->getContent();
        $this->assertStringContainsString('bg-ink text-white', substr($podcasty, (int) strpos($podcasty, '<footer')), '/podcasty ma stopkę szablonu FEER');

        $this->reset();
        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('bg-ink text-white', substr($home, (int) strpos($home, '<footer')), 'reszta witryny zostaje w szablonie domyślnym');
    }

    public function test_nieprawidlowy_szablon_jest_ignorowany(): void
    {
        $this->useSettings(['site_template' => 'default', 'podcasts_template' => 'nie-ma-takiego']);
        $this->get('/podcasty')->assertOk();
    }
}
