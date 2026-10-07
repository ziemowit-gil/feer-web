<?php

namespace Tests\Feature\Admin;

use App\Models\FeerBand;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeerBandsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_modul_jest_na_liscie_modulow_i_w_panelu(): void
    {
        $this->assertSame('FEER Paski (paski z tekstem i przyciskami)', SiteSetting::MODULES['feer_bands']);

        $this->actingAs($this->admin())->get(route('admin.feer-paski.index'))->assertOk()->assertSee('FEER Paski');
    }

    public function test_dodawanie_paska_z_walidacja_par_etykieta_link(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.feer-paski.store'), ['title' => 'Pasek', 'style' => 'brand', 'placement' => 'after_news', 'button_label' => 'Bez linku'])
            ->assertSessionHasErrors('button_url');

        $this->actingAs($admin)->post(route('admin.feer-paski.store'), [
            'title' => 'Twoje wsparcie tworzy zmianę', 'text' => 'Każda darowizna się liczy.', 'style' => 'brand', 'placement' => 'after_news',
            'button_label' => 'Wesprzyj nas', 'button_url' => '/wsparcie', 'button2_label' => 'Inne formy', 'button2_url' => '/wsparcie#sposoby', 'is_active' => '1',
        ])->assertRedirect(route('admin.feer-paski.index'));

        $this->assertSame(1, FeerBand::count());
    }

    public function test_pasek_pokazuje_sie_na_stronie_glownej_feer_we_wskazanym_miejscu(): void
    {
        FeerBand::create(['title' => 'Pasek po aktualnościach', 'style' => 'dark', 'placement' => 'after_news', 'button_label' => 'Więcej', 'button_url' => '/x', 'is_active' => true]);
        FeerBand::create(['title' => 'Pasek wyłączony', 'style' => 'brand', 'placement' => 'after_news', 'is_active' => false]);
        FeerBand::create(['title' => 'Pasek tylko skrót', 'style' => 'brand', 'placement' => 'shortcode', 'is_active' => true]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        $this->reset();

        $html = $this->get('/')->assertOk()->assertSee('Pasek po aktualnościach')->assertDontSee('Pasek wyłączony')->assertDontSee('Pasek tylko skrót')->getContent();
        $this->assertStringContainsString('bg-ink text-white', $html);
    }

    public function test_skrot_pasek_w_tresci_strony_i_wylaczenie_modulu(): void
    {
        $band = FeerBand::create(['title' => 'Pasek w treści', 'text' => 'Opis paska', 'style' => 'light', 'placement' => 'shortcode', 'button_label' => 'Zobacz', 'button_url' => '/projekty', 'is_active' => true]);
        Page::create(['title' => 'Strona', 'slug' => 'strona-z-paskiem', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Wstęp</p><p>'.$band->shortcode().'</p><p>Koniec</p>']);

        $this->get('/strona-z-paskiem')->assertOk()->assertSee('Pasek w treści')->assertSee('Opis paska')->assertSee('bg-brand-light', false);

        SiteSetting::current()->update(['disabled_modules' => ['feer_bands']]);
        $this->reset();
        $this->get('/strona-z-paskiem')->assertOk()->assertDontSee('Pasek w treści');
    }

    public function test_pasek_site_top_i_site_bottom_pojawia_sie_na_podstronach_ale_nie_na_glownej(): void
    {
        \App\Models\FeerBand::create(['title' => 'Pasek u góry', 'style' => 'brand', 'placement' => 'site_top', 'is_active' => true, 'order' => 1]);
        \App\Models\FeerBand::create(['title' => 'Pasek na dole', 'style' => 'dark', 'placement' => 'site_bottom', 'is_active' => true, 'order' => 1]);

        $this->get('/kontakt')->assertOk()->assertSee('Pasek u góry')->assertSee('Pasek na dole');
        $this->get('/')->assertOk()->assertDontSee('Pasek u góry')->assertDontSee('Pasek na dole');
    }
}
