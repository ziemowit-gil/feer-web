<?php

namespace Tests\Feature;

use App\Mail\AccessibilityReportMail;
use App\Models\AccessibilityReport;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the SiteSetting singleton cache between tests (see current()).
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_declaration_page_renders(): void
    {
        SiteSetting::current()->update([
            'site_name' => 'FEER',
            'accessibility_status' => 'partially',
            'accessibility_contact_email' => 'dostepnosc@feer.org.pl',
        ]);

        $this->get(route('accessibility.show'))
            ->assertOk()
            ->assertSee('Deklaracja dostępności')
            ->assertSee('częściowo zgodna')
            ->assertSee('dostepnosc@feer.org.pl')
            ->assertSee('Zgłoś problem z dostępnością');
    }

    public function test_deklaracja_ma_fakty_spis_tresci_i_sekcje(): void
    {
        SiteSetting::current()->update([
            'accessibility_status' => 'compliant',
            'accessibility_page_published_at' => '2026-07-27',
            'accessibility_declaration_date' => '2026-07-28',
            'accessibility_contact_name' => 'Anna Nowak',
            'accessibility_contact_email' => 'dostepnosc@feer.org.pl',
            'accessibility_contact_phone' => '+48 600 100 200',
        ]);

        $html = $this->get(route('accessibility.show'))->assertOk()->getContent();

        // Na górze: status i daty jako fakty, główna akcja do formularza.
        $this->assertMatchesRegularExpression('~<dl class="ad-facts">.*?Status zgodności z ustawą.*?fa-circle-check.*?Zgodna.*?Data publikacji strony.*?27 lipca 2026.*?Deklarację sporządzono.*?28 lipca 2026~s', $html);
        $this->assertStringContainsString('<a href="#zglos-bariere" class="ad-btn">', $html);
        // Spis treści z odnośnikami do sekcji; sekcje z nagłówkami h2.
        $this->assertMatchesRegularExpression('~<nav class="ad-toc" aria-labelledby="ad-toc-h" data-ad-toc>.*?href="#zgodnosc".*?href="#kontakt".*?href="#wylaczenia".*?href="#zglos-bariere"~s', $html);
        foreach (['zgodnosc', 'przygotowanie', 'skroty', 'kontakt', 'odwolanie', 'wylaczenia'] as $id) {
            $this->assertMatchesRegularExpression('~<section class="ad-sec" id="'.$id.'" aria-labelledby="'.$id.'-h">\s*<h2 class="ad-h2" id="'.$id.'-h">~', $html);
        }
        // Kontakt jako karty, kroki rozpatrzenia żądania, skróty klawiaturowe, odnośnik zewnętrzny zapowiada nową kartę.
        $this->assertStringContainsString('Anna Nowak', $html);
        $this->assertStringContainsString('href="tel:+48600100200"', $html);
        $this->assertMatchesRegularExpression('~<ol class="ad-steps">.*?Do 7 dni.*?Najdłużej 2 miesiące~s', $html);
        $this->assertStringContainsString('<kbd>Tab</kbd>', $html);
        $this->assertStringContainsString('Rzecznika Praw Obywatelskich<span class="sr-only"> (otwiera się w nowej karcie)</span>', $html);
        // Bez sekcji architektonicznej, gdy brak treści.
        $this->assertStringNotContainsString('href="#architektura"', $html);
    }

    public function test_pasek_panelu_ma_edycje_deklaracji_dla_administratora(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('accessibility.show'))->assertOk()
            ->assertSee('<span>Edytuj deklarację dostępności</span>', false)
            ->assertSee('tab=accessibility', false)
            ->assertSee('<span>Zgłoszenia barier</span>', false);

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->get(route('accessibility.show'))->assertOk()
            ->assertSee('data-site-admin-bar', false)
            ->assertDontSee('Edytuj deklarację dostępności');
    }

    public function test_barrier_report_is_stored_and_emailed(): void
    {
        Mail::fake();

        SiteSetting::current()->update(['accessibility_contact_email' => 'dostepnosc@feer.org.pl']);

        $this->post(route('accessibility.report'), [
            'name' => 'Anna Nowak',
            'email' => 'anna@example.com',
            'page_url' => '/aktualnosci',
            'message' => 'Przycisk „Zapisz" nie ma etykiety dla czytnika ekranu.',
            'rodo_consent' => '1',
            'website' => '',
        ])->assertRedirect(route('accessibility.show').'#zglos-bariere');

        $this->assertDatabaseHas('accessibility_reports', [
            'email' => 'anna@example.com',
            'page_url' => '/aktualnosci',
        ]);

        Mail::assertSent(AccessibilityReportMail::class, fn ($mail) => $mail->hasTo('dostepnosc@feer.org.pl'));
    }

    public function test_barrier_report_requires_consent_and_rejects_honeypot(): void
    {
        Mail::fake();

        $this->post(route('accessibility.report'), [
            'email' => 'anna@example.com',
            'message' => 'Opis bariery.',
            'website' => '', // brak zgody
        ])->assertSessionHasErrors('rodo_consent');

        $this->post(route('accessibility.report'), [
            'email' => 'anna@example.com',
            'message' => 'Opis bariery.',
            'rodo_consent' => '1',
            'website' => 'spam', // honeypot wypełniony
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseCount('accessibility_reports', 0);
        Mail::assertNothingSent();
    }

    public function test_admin_can_see_reports_and_settings_save_accessibility_fields(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        AccessibilityReport::create(['email' => 'anna@example.com', 'message' => 'Bariera.']);

        $this->actingAs($admin)
            ->get(route('admin.zgloszenia-barier.index'))
            ->assertOk()
            ->assertSee('anna@example.com');

        $this->actingAs($admin)
            ->put(route('admin.ustawienia.update'), [
                'site_name' => 'FEER',
                'brand_color' => '#c31432',
                'header_layout' => 'classic',
                'content_editor' => 'tinymce',
                'mail_transport' => 'default',
                'contact_address' => 'Barbackiego 28/18',
                'contact_city' => '33-300 Nowy Sącz',
                'contact_email' => 'kontakt@feer.org.pl',
                'accessibility_status' => 'compliant',
                'accessibility_contact_email' => 'dostepnosc@feer.org.pl',
            ])
            ->assertRedirect();

        $this->assertSame('compliant', SiteSetting::current()->fresh()->accessibility_status);
    }
}
