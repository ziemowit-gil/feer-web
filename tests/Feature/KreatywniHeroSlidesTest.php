<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Szablon „kreatywni”: slider na stronie głównej zarządzany w panelu (moduł Slajder). Slajdy z panelu zastępują
 * slajd powitalny z ustawień szablonu; bez nich, przy wyłączonym module albo poza terminem — slajd powitalny jak dawniej.
 */
class KreatywniHeroSlidesTest extends TestCase
{
    use RefreshDatabase;

    private function useTemplate(array $extra = []): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $site = SiteSetting::current();
        $site->forceFill(['site_template' => 'kreatywni'] + $extra)->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    private function slide(array $data): HeroSlide
    {
        return HeroSlide::create(['site_id' => SiteSetting::current()->id] + $data);
    }

    public function test_bez_slajdow_w_panelu_jest_slajd_powitalny(): void
    {
        $this->useTemplate();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<h1 id="kc-hero-title">Warsztaty kreatywne i twórcze</h1>', $html);
    }

    public function test_slajdy_z_panelu_zastepuja_powitanie_w_kolejnosci(): void
    {
        $this->useTemplate();
        $this->slide(['title' => 'Drugi slajd', 'text' => "Zwykły akapit\n- pierwszy punkt\n- drugi punkt\n1. krok jeden\n2. krok dwa", 'order' => 2]);
        $this->slide(['title' => 'Zapisy na ferie', 'text' => 'Zostało kilka miejsc.', 'button_label' => 'Zapisz się', 'button_url' => '/kontakt', 'button2_label' => 'Oferta warsztatów', 'button2_url' => '#oferta', 'order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<h1 id="kc-hero-title">Zapisy na ferie</h1>', $html);
        $this->assertStringContainsString('<h2 class="kc-slide-title">Drugi slajd</h2>', $html);
        $this->assertStringNotContainsString('Warsztaty kreatywne i twórcze</h1>', $html, 'powitanie znika, gdy są slajdy z panelu');
        $this->assertLessThan(strpos($html, 'Drugi slajd'), strpos($html, 'Zapisy na ferie'));
        $this->assertStringContainsString('Zapisz się', $html);
        $this->assertStringContainsString('href="/kontakt"', $html);
        $this->assertMatchesRegularExpression('#<a href="\#oferta" class="kc-btn kc-btn-ghost"\s*>\s*Oferta warsztatów#', $html, 'drugi przycisk slajdu');
        $this->assertMatchesRegularExpression('#<ul class="kc-points">\s*<li><i class="fa-solid fa-check" aria-hidden="true"></i>pierwszy punkt</li>\s*<li>#', $html);
        $this->assertMatchesRegularExpression('#<ol class="kc-steps kc-steps-hero">\s*<li>krok jeden</li>\s*<li>krok dwa</li>#', $html);
        $this->assertSame(1, substr_count($html, '<h1'), 'jeden H1 na stronie');
        $this->assertStringContainsString('Slajd 1: Zapisy na ferie', $html);
        // Slajd misji z ustawień szablonu zostaje po slajdach z panelu.
        $this->assertStringContainsString('Naszą misją jest', $html);
    }

    public function test_slajd_poza_terminem_i_wylaczony_modul_nie_sa_pokazywane(): void
    {
        $site = $this->useTemplate();
        $this->slide(['title' => 'Stary slajd', 'ends_at' => now()->subDay()]);

        $this->get('/')->assertOk()->assertDontSee('Stary slajd')->assertSee('Warsztaty kreatywne i twórcze');

        $this->slide(['title' => 'Aktualny slajd']);
        $this->get('/')->assertOk()->assertSee('<h1 id="kc-hero-title">Aktualny slajd</h1>', false);

        $this->useTemplate(['disabled_modules' => array_values(array_unique([...($site->disabled_modules ?? []), 'hero']))]);
        $this->get('/')->assertOk()->assertDontSee('Aktualny slajd')->assertSee('Warsztaty kreatywne i twórcze');
    }
}
