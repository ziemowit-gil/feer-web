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
            ->assertSee('Stan witryny teraz')->assertSee('Dostępność witryny')->assertSee('Komunikaty i wygląd')
            ->assertSee('Żałoba narodowa')->assertSee('name="mourning_from"', false)->assertSee('name="maintenance_mode"', false)
            ->assertSee('name="under_construction"', false)->assertSee('name="notice_enabled"', false)->assertSee('name="site_url"', false);
    }
}
