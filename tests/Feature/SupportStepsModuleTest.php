<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł „Jak wesprzeć w trzech krokach” na stronie /wsparcie: domyślne teksty, edycja w panelu, ukrywanie. */
class SupportStepsModuleTest extends TestCase
{
    use RefreshDatabase;

    private function resetSettings(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_strona_wsparcia_pokazuje_trzy_kroki_z_domyslnymi_tekstami(): void
    {
        $this->resetSettings();

        $html = $this->get(route('support.show'))->assertOk()->getContent();

        $this->assertStringContainsString('Jak wesprzeć w trzech krokach', $html);
        foreach (['Wybierz sposób', 'Wpłać', 'Zdecyduj o liście wpłat'] as $step) {
            $this->assertStringContainsString($step, $html);
        }
        $this->assertStringContainsString('<ol class="mt-8 grid gap-6"', $html);
        $this->assertStringContainsString('Krok 1: ', $html);
    }

    public function test_wlasne_teksty_i_ukrycie_sekcji(): void
    {
        $this->resetSettings();
        SiteSetting::current()->update(['support_steps_title' => 'Trzy proste ruchy', 'support_step1_title' => 'Zacznij tutaj']);
        $this->resetSettings();

        $html = $this->get(route('support.show'))->assertOk()->getContent();
        $this->assertStringContainsString('Trzy proste ruchy', $html);
        $this->assertStringContainsString('Zacznij tutaj', $html);
        $this->assertStringNotContainsString('Jak wesprzeć w trzech krokach', $html);

        SiteSetting::current()->update(['support_steps_hidden' => true]);
        $this->resetSettings();
        $hidden = $this->get(route('support.show'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Trzy proste ruchy', $hidden);
        $this->assertStringNotContainsString('id="steps-heading"', $hidden);
    }

    public function test_panel_pokazuje_pola_modulu_i_zapisuje_zmiany(): void
    {
        $this->resetSettings();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $edit = $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'support']))->assertOk()->getContent();
        $this->assertStringContainsString('Jak wesprzeć w trzech krokach', $edit);
        $this->assertStringContainsString('name="support_step2_text"', $edit);
        $this->assertStringContainsString('name="support_steps_hidden"', $edit);
    }

    public function test_zapis_z_formularza_ustawien_zapisuje_kroki_i_przelacznik_ukrycia(): void
    {
        $this->resetSettings();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $base = [
            'site_name' => 'Fundacja Test', 'brand_color' => '#c31432', 'header_layout' => 'classic',
            'content_editor' => 'tinymce', 'mail_transport' => 'default',
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
        ];

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), $base + [
            'support_steps_title' => 'Trzy proste ruchy', 'support_step2_text' => 'Wpłać BLIK-iem.', 'support_steps_hidden' => '1',
        ])->assertSessionHasNoErrors();
        $this->resetSettings();

        $settings = SiteSetting::current();
        $this->assertSame('Trzy proste ruchy', $settings->support_steps_title);
        $this->assertSame('Wpłać BLIK-iem.', $settings->support_step2_text);
        $this->assertTrue($settings->support_steps_hidden);

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), $base + ['support_steps_hidden' => '0'])->assertSessionHasNoErrors();
        $this->resetSettings();
        $this->assertFalse(SiteSetting::current()->support_steps_hidden);
    }
}
