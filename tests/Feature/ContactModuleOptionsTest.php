<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ContactForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contact\Support\ContactOptions;
use Tests\TestCase;

/**
 * Moduł Kontakt — opcje (Komunikacja → Kontakt): formularz na stronie, czas odpowiedzi, podstrony w menu,
 * „Gdzie działamy”, dojazd i dostępność, mapa, vCard, komunikat o zmianie godzin; ekran panelu i jego zapis.
 */
class ContactModuleOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    /** @param array<string, mixed> $options */
    private function setOptions(array $options, array $settings = []): void
    {
        SiteSetting::current()->forceFill($settings + ['contact_options' => ContactOptions::normalize($options)])->save();
        $this->reset();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    // ── Domyślnie bez zmian ───────────────────────────────────────────────────

    public function test_bez_opcji_strona_kontaktu_wyglada_jak_dotad(): void
    {
        SiteSetting::current()->update(['contact_layout' => 'tabs']); // układ ze zdaniem o czasie odpowiedzi
        $this->reset();
        $html = $this->get('/kontakt')->assertOk()->getContent();

        $this->assertStringContainsString(ContactOptions::RESPONSE_TIME_DEFAULT, $html);
        foreach (['contact-closure', 'contact-visit', 'contact-form-off', 'Pokaż na mapie', 'kontakt.vcf'] as $absent) {
            $this->assertStringNotContainsString($absent, $html, $absent);
        }
    }

    // ── Strona publiczna ──────────────────────────────────────────────────────

    public function test_wylaczony_formularz_zostawia_informacje_z_adresem(): void
    {
        $this->setOptions(['form_enabled' => false], ['contact_email' => 'biuro@example.org', 'contact_phone' => '12 210 06 98', 'site_template' => 'feer', 'contact_layout' => 'tabs']);

        $html = $this->get('/kontakt')->assertOk()->getContent();
        $this->assertStringContainsString('contact-form-off', $html);
        $this->assertStringContainsString('biuro@example.org', $html);
        $this->assertStringNotContainsString(ContactOptions::RESPONSE_TIME_DEFAULT, $html);
        $this->assertStringNotContainsString('name="message"', $html);
    }

    public function test_wlasny_czas_odpowiedzi_i_puste_zdanie(): void
    {
        $this->setOptions(['response_time' => 'Odpisujemy w ciągu dwóch dni.'], ['contact_layout' => 'tabs']);
        $this->get('/kontakt')->assertOk()->assertSee('Odpisujemy w ciągu dwóch dni.')->assertDontSee(ContactOptions::RESPONSE_TIME_DEFAULT);

        $this->setOptions(['response_time' => ''], ['contact_layout' => 'tabs']);
        $this->get('/kontakt')->assertOk()->assertDontSee(ContactOptions::RESPONSE_TIME_DEFAULT);
    }

    public function test_dojazd_dostepnosc_mapa_i_vcard_we_wszystkich_ukladach(): void
    {
        foreach (['classic', 'card', 'split', 'tabs', 'feer'] as $layout) {
            $this->setOptions(
                ['directions' => 'Tramwaj 4, przystanek Dworzec.', 'accessibility' => 'Wejście bez progów, winda.', 'map_link' => true, 'vcard' => true],
                ['contact_layout' => $layout, 'site_template' => $layout === 'feer' ? 'feer' : 'default', 'contact_address' => 'Zamknięta 10/1.5', 'contact_city' => '30-554 Kraków'],
            );

            $html = $this->get('/kontakt')->assertOk()->getContent();
            $this->assertStringContainsString('Tramwaj 4, przystanek Dworzec.', $html, $layout);
            $this->assertStringContainsString('Wejście bez progów, winda.', $html, $layout);
            $this->assertStringContainsString('https://www.openstreetmap.org/search?query='.rawurlencode('Zamknięta 10/1.5, 30-554 Kraków'), $html, $layout);
            $this->assertStringContainsString(url('/kontakt/kontakt.vcf'), $html, $layout);
        }
    }

    public function test_komunikat_o_nieobecnosci_tylko_w_podanych_dniach(): void
    {
        $this->setOptions(['closure_text' => 'Biuro nieczynne między świętami.', 'closure_from' => today()->subDay()->toDateString(), 'closure_until' => today()->addDay()->toDateString()]);
        $this->get('/kontakt')->assertOk()->assertSee('Biuro nieczynne między świętami.')->assertSee('contact-closure', false);

        $this->setOptions(['closure_text' => 'Biuro nieczynne między świętami.', 'closure_from' => today()->addDay()->toDateString()]);
        $this->get('/kontakt')->assertOk()->assertDontSee('Biuro nieczynne między świętami.');

        $this->setOptions(['closure_text' => 'Biuro nieczynne między świętami.', 'closure_until' => today()->subDay()->toDateString()]);
        $this->get('/kontakt')->assertOk()->assertDontSee('Biuro nieczynne między świętami.');

        // Bez dat: zawsze, dopóki ma treść.
        $this->setOptions(['closure_text' => 'Zmiana godzin w lipcu.']);
        $this->get('/kontakt')->assertOk()->assertSee('Zmiana godzin w lipcu.');
    }

    public function test_vcard_tylko_po_wlaczeniu_z_danymi_organizacji(): void
    {
        $this->get('/kontakt/kontakt.vcf')->assertNotFound();

        $this->setOptions(['vcard' => true], ['site_name' => 'Fundacja; Test', 'contact_phone' => '12 210 06 98', 'contact_email' => 'biuro@example.org',
            'contact_address' => 'Zamknięta 10/1.5', 'contact_city' => '30-554 Kraków']);

        $res = $this->get('/kontakt/kontakt.vcf')->assertOk();
        $this->assertStringStartsWith('text/vcard', $res->headers->get('Content-Type'));
        $body = $res->getContent();
        $this->assertStringContainsString("BEGIN:VCARD\r\nVERSION:3.0\r\n", $body);
        $this->assertStringContainsString('FN:Fundacja\; Test', $body);
        $this->assertStringContainsString('TEL;TYPE=WORK,VOICE:12 210 06 98', $body);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET,WORK:biuro@example.org', $body);
        $this->assertStringContainsString('ADR;TYPE=WORK:;;Zamknięta 10/1.5;Kraków;;30-554;Polska', $body);
    }

    public function test_gdzie_dzialamy_jako_podstrona_w_menu_bocznym(): void
    {
        $this->setOptions([
            'places_intro' => 'Działamy głównie online.',
            'places' => [
                ['name' => 'Klub Cyfrowy', 'area' => 'Nowy Sącz', 'description' => 'Zajęcia komputerowe dla osób 60+.', 'address' => 'ul. Długa 1, Nowy Sącz', 'url' => '/dzialania/klub', 'url_label' => 'O Klubie'],
                ['name' => 'Online', 'description' => 'Szkolenia i konsultacje na platformie.'],
            ],
        ], ['site_template' => 'feer', 'contact_layout' => 'tabs']);

        $nav = $this->get('/kontakt')->assertOk()->getContent();
        $this->assertStringContainsString('href="'.url('/kontakt/gdzie-dzialamy').'"', $nav);

        $this->get('/kontakt/gdzie-dzialamy')->assertOk()
            ->assertSee('Gdzie działamy')->assertSee('Działamy głównie online.')
            ->assertSee('Klub Cyfrowy')->assertSee('Nowy Sącz')->assertSee('Zajęcia komputerowe dla osób 60+.')
            ->assertSee('O Klubie')->assertSee('openstreetmap.org/search?query=', false)
            ->assertSee('Szkolenia i konsultacje na platformie.')
            ->assertSee('aria-current="page"', false);
    }

    public function test_ukryta_podstrona_znika_z_menu_i_daje_404(): void
    {
        $this->setOptions(['hidden_sections' => ['dane-rejestrowe']], ['site_template' => 'feer', 'contact_layout' => 'tabs', 'krs_number' => '0000779281',
            'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']]]);

        $html = $this->get('/kontakt')->assertOk()->getContent();
        $this->assertStringNotContainsString(url('/kontakt/dane-rejestrowe'), $html);
        $this->assertStringContainsString(url('/kontakt/rachunki'), $html);
        $this->get('/kontakt/dane-rejestrowe')->assertNotFound();
    }

    public function test_normalizacja_opcji(): void
    {
        $o = ContactOptions::normalize([
            'form_enabled' => '0', 'map_link' => '1', 'hidden_sections' => ['rachunki', 'nie-ma-takiej'],
            'places' => [['name' => ''], ['name' => 'A', 'url' => 'javascript:alert(1)'], ['name' => 'B', 'url' => '//zly.pl'], ['name' => 'C', 'url' => 'https://ok.pl/x']],
            'closure_from' => 'wczoraj', 'closure_until' => '2026-12-31',
        ]);

        $this->assertFalse($o['form_enabled']);
        $this->assertTrue($o['map_link']);
        $this->assertSame(['rachunki'], $o['hidden_sections']);
        $this->assertSame(['A', 'B', 'C'], array_column($o['places'], 'name'));
        $this->assertSame(['', '', 'https://ok.pl/x'], array_column($o['places'], 'url'));
        $this->assertNull($o['closure_from']);
        $this->assertSame('2026-12-31', $o['closure_until']);
        $this->assertSame(ContactOptions::RESPONSE_TIME_DEFAULT, $o['response_time'], 'brak klucza = zdanie domyślne');
    }

    // ── Panel: Komunikacja → Kontakt ──────────────────────────────────────────

    public function test_panel_pokazuje_ekran_modulu_i_pozycje_w_menu(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.kontakt.index'))->assertOk()->getContent();

        foreach (['Formularz kontaktowy', 'Podstrony w menu bocznym kontaktu', 'Gdzie działamy', 'Dojazd i dostępność biura', 'Komunikat o zmianie godzin', 'Zapisz opcje kontaktu'] as $part) {
            $this->assertStringContainsString($part, $html, $part);
        }
        $this->assertStringContainsString('href="'.route('admin.kontakt.index').'"', $html); // pozycja w menu bocznym panelu
    }

    public function test_zapis_opcji_i_ustawien_formularza(): void
    {
        $form = ContactForm::definition();
        $this->assertInstanceOf(FormDefinition::class, $form);

        $this->actingAs($this->admin())->put(route('admin.kontakt.update'), [
            'options' => [
                'form_enabled' => '1', 'response_time' => 'Odpisujemy szybko.',
                'shown_sections' => ['spotkania', 'gdzie-dzialamy', 'rachunki'],
                'places_title' => 'Nasze miejsca',
                'places' => [['name' => 'Kraków', 'description' => 'Biuro i szkolenia.'], ['name' => '', 'description' => '']],
                'directions' => 'Tramwaj 4.', 'map_link' => '1', 'vcard' => '0',
                'closure_text' => 'Przerwa świąteczna.', 'closure_from' => '2026-12-24', 'closure_until' => '2027-01-01',
            ],
            'form' => [
                'is_active' => '1', 'notification_email' => 'a@example.org, b@example.org', 'notification_subject' => 'Nowa wiadomość',
                'confirmation_message' => 'Dziękujemy!', 'submit_label' => 'Wyślij', 'reply_to_submitter' => '1',
                'send_copy_to_submitter' => '1', 'submitter_copy_message' => 'Kopia Twojej wiadomości.',
            ],
        ])->assertRedirect(route('admin.kontakt.index'));

        $this->reset();
        $o = SiteSetting::current()->contact_options;
        $this->assertSame('Odpisujemy szybko.', $o['response_time']);
        $this->assertSame(['przesylki', 'oddzialy', 'dane-rejestrowe'], $o['hidden_sections']);
        $this->assertSame('Nasze miejsca', $o['places_title']);
        $this->assertSame(['Kraków'], array_column($o['places'], 'name'));
        $this->assertTrue($o['map_link']);
        $this->assertFalse($o['vcard']);
        $this->assertSame(['2026-12-24', '2027-01-01'], [$o['closure_from'], $o['closure_until']]);

        $s = $form->fresh()->settings;
        $this->assertSame('a@example.org, b@example.org', $s['notification_email']);
        $this->assertSame('Dziękujemy!', $s['confirmation_message']);
        $this->assertTrue($s['send_copy_to_submitter']);
        $this->assertSame('Kopia Twojej wiadomości.', $s['submitter_copy_message']);
        $this->assertTrue($form->fresh()->is_active);
    }

    public function test_bledny_adres_powiadomien_i_daty_sa_odrzucane(): void
    {
        ContactForm::definition();

        $this->actingAs($this->admin())->from(route('admin.kontakt.index'))->put(route('admin.kontakt.update'), [
            'options' => ['closure_from' => '2026-12-24', 'closure_until' => '2026-12-01'],
            'form' => ['notification_email' => 'biuro@example.org, to-nie-mail'],
        ])->assertRedirect(route('admin.kontakt.index'))->assertSessionHasErrors(['options.closure_until', 'form.notification_email']);
    }

    public function test_redaktor_bez_dostepu_do_modulu_nie_wejdzie(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->actingAs($editor)->get(route('admin.kontakt.index'))->assertForbidden();
    }
}
