<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Stopka edytowana przez administratora (Ustawienia → Ogólne → Stopka) i skrót „Edytuj stopkę” w stopce FEER. */
class FooterEditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
        SiteSetting::current()->update(['site_template' => 'feer']);
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function footer(string $html): string
    {
        return substr($html, strpos($html, '<footer'));
    }

    public function test_domyslnie_stopka_wyglada_jak_dotad(): void
    {
        $footer = $this->footer($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('© '.now()->year.' Wszystkie prawa zastrzeżone', $footer);
        $this->assertStringContainsString('Deklaracja dostępności', $footer);
        $this->assertStringContainsString('Mapa strony', $footer);
        $this->assertStringContainsString('aria-label="Strefa współpracownika"', $footer);
        $this->assertStringContainsString('aria-label="Panel administracyjny"', $footer);
        $this->assertStringNotContainsString('data-footer-edit', $footer, 'gość nie widzi skrótu edycji');
    }

    public function test_administrator_zmienia_stopke_w_ustawieniach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['footer_options[copyright]', 'footer_options[show_sitemap]', 'footer_options[staff_link]', 'footer_options[staff_url]', 'footer_options[staff_label]', 'footer_options[show_admin_link]'],
            'footer_options' => [
                'copyright' => 'Fundacja FEER {rok} · KRS 0000779281',
                'show_accessibility' => '1', 'show_sitemap' => '0', 'show_cookies' => '1', 'show_rss' => '0',
                'staff_link' => '1', 'staff_url' => '/dla-wspolpracownikow', 'staff_label' => 'Dla współpracowników',
                'show_admin_link' => '0',
            ],
        ])->assertSessionHasNoErrors();
        $this->reset();

        auth()->logout();
        $footer = $this->footer($this->get('/')->assertOk()->getContent());
        $this->assertStringContainsString('Fundacja FEER '.now()->year.' · KRS 0000779281', $footer);
        $this->assertStringNotContainsString('Wszystkie prawa zastrzeżone', $footer);
        $this->assertStringNotContainsString('Mapa strony', $footer);
        $this->assertStringContainsString('Deklaracja dostępności', $footer);
        $this->assertStringContainsString('href="'.url('/dla-wspolpracownikow').'" aria-label="Dla współpracowników"', $footer);
        $this->assertStringNotContainsString('aria-label="Panel administracyjny"', $footer);
        $this->assertStringNotContainsString('Kanał RSS', $footer);
    }

    public function test_zapis_innej_sekcji_nie_zeruje_stopki(): void
    {
        SiteSetting::current()->update(['footer_options' => SiteSetting::normalizeFooterOptions(['copyright' => 'Mój tekst'])]);
        $this->reset();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['tagline'], 'tagline' => 'Nowy podtytuł',
        ])->assertSessionHasNoErrors();
        $this->reset();

        $this->assertSame('Mój tekst', SiteSetting::current()->footerOptions()['copyright']);
    }

    public function test_bledny_adres_odnosnika_jest_odrzucany(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['footer_options[staff_url]'],
            'footer_options' => ['staff_url' => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('footer_options.staff_url');
    }

    public function test_administrator_widzi_w_stopce_skrot_do_edycji_i_pola_w_ustawieniach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/')->assertOk()->assertSee('data-footer-edit', false)->assertSee('Edytuj stopkę');
        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'general']))->assertOk()
            ->assertSee('name="footer_options[copyright]"', false)->assertSee('name="footer_options[staff_url]"', false);
    }
}
