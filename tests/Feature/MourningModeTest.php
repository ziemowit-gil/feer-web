<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Tryb żałobny: witryna publiczna w odcieniach szarości, gdy włączony w Ustawienia → Serwis. */
class MourningModeTest extends TestCase
{
    use RefreshDatabase;

    private function setMourning(bool $on, array $extra = []): void
    {
        $reset = fn () => \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $reset();
        SiteSetting::current()->forceFill(['mourning_mode' => $on] + $extra)->save();
        $reset();
    }

    public function test_wlaczony_tryb_zalobny_dodaje_filtr_szarosci(): void
    {
        $this->setMourning(true);

        $this->get('/')->assertOk()->assertSee('class="mourning-mode"', false)->assertSee('grayscale(1)', false);
    }

    public function test_zalogowany_administrator_widzi_witryne_w_kolorach_mimo_zalobu(): void
    {
        $this->setMourning(true);
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/')->assertOk()->assertDontSee('mourning-mode', false)->assertDontSee('grayscale(1)', false);
        // Odwiedzający (niezalogowany) nadal widzi żałobę.
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('class="mourning-mode"', false);
    }

    public function test_zalogowany_redaktor_nie_administrator_widzi_zalobe(): void
    {
        $this->setMourning(true);
        $editor = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_EDITOR]);

        $this->actingAs($editor)->get('/')->assertOk()->assertSee('class="mourning-mode"', false);
    }

    public function test_domyslnie_witryna_jest_kolorowa(): void
    {
        $this->setMourning(false);

        $this->get('/')->assertOk()->assertDontSee('mourning-mode', false)->assertDontSee('grayscale(1)', false);
    }

    public function test_zaplanowany_przedzial_wlacza_i_wylacza_zalobe_sam(): void
    {
        $this->setMourning(false, ['mourning_from' => today()->subDay(), 'mourning_until' => today()]);
        $this->get('/')->assertOk()->assertSee('class="mourning-mode"', false);

        $this->setMourning(false, ['mourning_from' => today()->subDays(3), 'mourning_until' => today()->subDay()]);
        $this->get('/')->assertOk()->assertDontSee('mourning-mode', false);

        $this->setMourning(false, ['mourning_from' => today()->addDay(), 'mourning_until' => today()->addDays(3)]);
        $this->get('/')->assertOk()->assertDontSee('mourning-mode', false);
    }

    public function test_zakladka_serwis_pokazuje_stan_witryny_i_wszystkie_sekcje(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'maintenance']))->assertOk()
            ->assertSee('Stan witryny teraz')->assertSee('Przerwa techniczna')->assertSee('Strona „dopiero tworzymy”')->assertSee('Ramka z komunikatem')
            ->assertSee('Żałoba narodowa')->assertSee('name="mourning_from"', false)->assertSee('name="maintenance_mode"', false)
            ->assertSee('name="under_construction"', false)->assertSee('name="notice_enabled"', false)->assertSee('name="site_url"', false);
    }

    public function test_zakladka_ogolne_ma_sekcje_i_wszystkie_pola(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'general']))->assertOk()
            ->assertSee('Tożsamość strony')->assertSee('Edycja treści i integracje')->assertSee('Pliki cookies')->assertSee('Stopka')->getContent();

        foreach (['site_name', 'site_name_genitive', 'tagline', 'content_editor', 'unsplash_access_key', 'cookie_banner_enabled', 'cookie_banner_text', 'show_cms_credit', 'logo', 'logo_alt', 'logo_only'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html, $field);
        }
    }
}
