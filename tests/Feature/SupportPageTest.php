<?php

namespace Tests\Feature;

use App\Models\ContentRevision;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportPageTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_strona_pokazuje_kafelki_efektu_kwot_z_linkiem_do_darowizny(): void
    {
        SiteSetting::current()->forceFill([
            'donation_amounts' => '30, 60, 100',
            'donation_impacts' => "60 | Materiały dla jednej grupy\n100 | Warsztat dla klasy\n999 | Kwota spoza listy",
        ])->save();
        $this->reset();

        $this->get(route('support.show'))->assertOk()
            ->assertSee('Co daje Twoja wpłata')
            ->assertSee('Materiały dla jednej grupy')
            ->assertSee('Warsztat dla klasy')
            ->assertDontSee('Kwota spoza listy')
            ->assertSee(route('donation.show', ['kwota' => 60]), false)
            ->assertSee('Najczęstsze pytania')
            ->assertSee('Nie możesz wpłacić? Pomóż inaczej')
            ->assertSee('id="support-sticky"', false);
    }

    public function test_bez_opisow_efektu_sekcja_kafelkow_sie_nie_pojawia(): void
    {
        $this->get(route('support.show'))->assertOk()->assertDontSee('Co daje Twoja wpłata');
    }

    public function test_faq_z_panelu_zastepuje_domyslne_a_bledne_linie_sa_pomijane(): void
    {
        SiteSetting::current()->forceFill(['support_faq' => "Własne pytanie? | Własna odpowiedź.\nlinia bez separatora\n | pusty"])->save();
        $this->reset();

        $this->assertSame([['q' => 'Własne pytanie?', 'a' => 'Własna odpowiedź.']], SiteSetting::current()->supportFaq());
        $this->get(route('support.show'))->assertOk()->assertSee('Własne pytanie?')->assertDontSee('Czy płatność online jest bezpieczna?');
    }

    public function test_darowizna_wstepnie_zaznacza_kwote_z_adresu_tylko_gdy_jest_na_liscie(): void
    {
        config(['przelewy24.merchant_id' => '1', 'przelewy24.pos_id' => '1', 'przelewy24.crc' => 'x', 'przelewy24.api_key' => 'y']);
        SiteSetting::current()->forceFill(['donation_amounts' => '30, 60, 250'])->save();
        $this->reset();

        $html = $this->get(route('donation.show', ['kwota' => 250]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="donation-amount-250"[^>]*checked/', preg_replace('/\s+/', ' ', $html));

        $html = $this->get(route('donation.show', ['kwota' => 7]))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="donation-amount-7"/', $html);
    }

    public function test_zmiana_tekstow_wsparcia_tworzy_wersje_a_inna_zmiana_nie(): void
    {
        $settings = SiteSetting::current();
        $before = ContentRevision::where('revisionable_type', SiteSetting::class)->count();

        $settings->update(['site_name' => 'Inna nazwa']);
        $this->assertSame($before, ContentRevision::where('revisionable_type', SiteSetting::class)->count(), 'Pola spoza strony wsparcia nie tworzą wersji.');

        $this->actingAs($this->admin());
        $settings->update(['support_hero_title' => 'Nowy tytuł']);

        $revision = ContentRevision::where('revisionable_type', SiteSetting::class)->latest('id')->first();
        $this->assertSame($before + 1, ContentRevision::where('revisionable_type', SiteSetting::class)->count());
        $this->assertSame('Nowy tytuł', $revision->data['support_hero_title']);
        $this->assertArrayNotHasKey('site_name', $revision->data);
        $this->assertNotNull($revision->user_id);
    }

    public function test_historia_pokazuje_roznice_i_pozwala_przywrocic_wersje(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $settings = SiteSetting::current();
        $settings->update(['support_hero_title' => 'Wersja A']);
        $versionA = ContentRevision::where('revisionable_type', SiteSetting::class)->latest('id')->first();
        $settings->update(['support_hero_title' => 'Wersja B']);

        $this->get(route('admin.historia.index', ['type' => 'support', 'id' => $settings->id]))
            ->assertOk()->assertSee('Historia zmian')->assertSee('Nagłówek: tytuł')->assertSee('Strona wsparcia');

        $this->post(route('admin.historia.restore', ['type' => 'support', 'id' => $settings->id, 'revision' => $versionA->id]))
            ->assertRedirect(route('admin.ustawienia.edit', ['tab' => 'support']));

        $this->assertSame('Wersja A', $settings->fresh()->support_hero_title);
    }

    public function test_historia_wersji_ustawien_jest_tylko_dla_administratora(): void
    {
        $settings = SiteSetting::current();
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);

        $this->actingAs($editor)->get(route('admin.historia.index', ['type' => 'support', 'id' => $settings->id]))->assertForbidden();
    }

    public function test_panel_ustawien_ma_link_do_historii_i_pole_faq(): void
    {
        $this->actingAs($this->admin())->get(route('admin.ustawienia.edit'))
            ->assertOk()->assertSee('Historia wersji tej strony')->assertSee('name="support_faq"', false);
    }
}
