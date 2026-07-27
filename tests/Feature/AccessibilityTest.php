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
