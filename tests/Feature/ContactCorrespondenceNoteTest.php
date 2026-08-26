<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactCorrespondenceNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function settings(array $attributes = []): SiteSetting
    {
        $settings = SiteSetting::current();
        $settings->update($attributes);

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return $settings->fresh();
    }

    public function test_uwaga_o_korespondencji_pokazuje_sie_na_stronie_kontaktu(): void
    {
        $this->settings([
            'contact_correspondence_note' => 'Pisma urzędowe kierujcie na adres e-Doręczeń.',
        ]);

        $this->get(route('contact.show'))
            ->assertOk()
            ->assertSee('Pisma urzędowe kierujcie na adres e-Doręczeń.')
            ->assertSee('Ważne: kierowanie korespondencji')
            ->assertSee('role="note"', false);
    }

    public function test_wlasny_naglowek_zastepuje_domyslny(): void
    {
        $this->settings([
            'contact_correspondence_title' => 'Gdzie wysyłać pisma',
            'contact_correspondence_note'  => 'Na adres skrytki pocztowej.',
        ]);

        $this->get(route('contact.show'))
            ->assertOk()
            ->assertSee('Gdzie wysyłać pisma')
            ->assertDontSee('Ważne: kierowanie korespondencji');
    }

    public function test_przejscia_do_nowej_linii_sa_zachowane_a_html_uciekany(): void
    {
        $this->settings([
            'contact_correspondence_note' => "Skrytka pocztowa 15\n<script>alert(1)</script>",
        ]);

        $html = $this->get(route('contact.show'))->assertOk()->getContent();

        $this->assertStringContainsString('Skrytka pocztowa 15<br />', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_bez_tresci_ramka_sie_nie_pokazuje(): void
    {
        $this->settings(['contact_correspondence_title' => 'Sam nagłówek bez treści']);

        $this->get(route('contact.show'))
            ->assertOk()
            ->assertDontSee('Sam nagłówek bez treści');
    }

    public function test_admin_zapisuje_uwage_z_zakladki_kontakt(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $settings = SiteSetting::current();

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            'site_name'                    => $settings->site_name ?: 'FEER',
            'brand_color'                  => '#8b1538',
            'header_layout'                => $settings->header_layout ?: 'classic',
            'content_editor'               => $settings->content_editor ?: 'tinymce',
            'mail_transport'               => $settings->mail_transport ?: 'default',
            'contact_address'              => 'ul. Testowa 1',
            'contact_city'                 => '00-001 Warszawa',
            'contact_email'                => 'kontakt@example.pl',
            'contact_correspondence_title' => 'Kierowanie korespondencji',
            'contact_correspondence_note'  => 'Wyłącznie na e-Doręczenia.',
            '_redirect_tab'                => 'contact',
        ])->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'contact']));

        $settings->refresh();

        $this->assertSame('Kierowanie korespondencji', $settings->contact_correspondence_title);
        $this->assertSame('Wyłącznie na e-Doręczenia.', $settings->contact_correspondence_note);
    }
}
