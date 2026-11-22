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
        // Z panelu steruje się całym sliderem: slajdów misji i social media z ustawień szablonu wtedy nie ma.
        $this->assertStringNotContainsString('<h2 class="kc-slide-title">Naszą misją jest</h2>', $html);
        $this->assertSame(2, substr_count($html, 'aria-roledescription="slajd"'));
    }

    public function test_bez_slajdow_w_panelu_slider_domyslny_ma_misje(): void
    {
        $this->useTemplate();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<h2 class="kc-slide-title">Naszą misją jest</h2>', $html);
    }

    public function test_panel_sliderow_w_kreatywni_bez_panelu_slajdu_misji(): void
    {
        $this->useTemplate();
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);

        $home = \App\Models\Slider::create(['site_id' => \App\Models\SiteSetting::current()->id, 'name' => 'Strona główna', 'is_home' => true]);

        $this->actingAs($admin)->get(route('admin.slidery.show', $home))->assertOk()
            ->assertSee('slider na stronie głównej składa się tylko z nich')
            ->assertDontSee('Misja organizacji jako pierwszy slajd');
    }

    public function test_zdjecie_slajdu_ma_opis_z_panelu(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->useTemplate();
        $slide = $this->slide(['title' => 'Ze zdjęciem', 'image_alt' => 'Uczestnicy lepią z gliny']);
        $slide->addMedia(\Illuminate\Http\UploadedFile::fake()->image('s.jpg', 800, 800))->toMediaCollection('image');

        $this->get('/')->assertOk()->assertSee('alt="Uczestnicy lepią z gliny"', false);
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
