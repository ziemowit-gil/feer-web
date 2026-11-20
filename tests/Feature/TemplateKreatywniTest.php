<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\KreatywniTexts;
use Database\Seeders\KreatywniSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Szablon „kreatywni” (wizytówka fundacji „Kreatywni na co dzień”): rejestracja, strona główna z tekstami domyślnymi
 * i własnymi, ukrywanie sekcji, social media z ustawień, nagłówek i stopka, zapis tekstów w panelu (także bez
 * migracji) oraz seeder treści startowych.
 */
class TemplateKreatywniTest extends TestCase
{
    use RefreshDatabase;

    private function resetCache(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function useTemplate(array $extra = []): SiteSetting
    {
        $this->resetCache();
        $site = SiteSetting::current();
        $site->forceFill(['site_template' => 'kreatywni'] + $extra)->save();
        $this->resetCache();

        return SiteSetting::current();
    }

    public function test_szablon_jest_w_ustawieniach_i_w_wersji_platnej(): void
    {
        $this->assertArrayHasKey('kreatywni', SiteSetting::SITE_TEMPLATES);
        $this->assertArrayHasKey('kreatywni', SiteSetting::SITE_TEMPLATE_BRIEF);
        $this->assertContains('kreatywni', config('editions.paid_templates'));
    }

    public function test_strona_glowna_ma_tresci_domyslne_naglowek_i_stopke(): void
    {
        $this->useTemplate(['contact_phone' => '+48 663 182 816', 'contact_email' => 'warsztaty@example.pl']);
        NavItem::create(['label' => 'Oferta', 'url' => '/oferta', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="kc kc-header"', $html);
        $this->assertStringContainsString('aria-label="Menu główne"', $html);
        $this->assertStringContainsString('>Oferta</a>', $html);
        $this->assertStringContainsString('<h1 id="kc-hero-title">Warsztaty kreatywne i twórcze</h1>', $html);
        $this->assertStringContainsString('Naszą misją jest', $html);
        $this->assertStringContainsString('Laleczki motanki', $html);
        $this->assertStringContainsString('Cena od 30 zł za osobę', $html);
        $this->assertStringContainsString('Co nas wyróżnia?', $html);
        $this->assertStringContainsString('Anna Mossór-Koper', $html);
        $this->assertStringContainsString('<figcaption>Justyna</figcaption>', $html);
        $this->assertStringContainsString('href="tel:+48663182816"', $html);
        $this->assertStringContainsString('class="kc kc-dark kc-footer"', $html);
        // Jeden nagłówek H1 na stronie.
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_sekcja_social_media_pokazuje_profile_z_ustawien_a_bez_nich_znika(): void
    {
        $this->useTemplate(['facebook_url' => null, 'instagram_url' => null, 'youtube_url' => null, 'linkedin_url' => null, 'twitter_url' => null, 'facebook_group_url' => null, 'substack_url' => null]);
        $this->get('/')->assertOk()->assertDontSee('id="social"', false);

        $this->useTemplate(['facebook_url' => 'https://www.facebook.com/profile.php?id=1', 'instagram_url' => 'https://www.instagram.com/fundacja.kreatywni/']);
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="social"', $html);
        $this->assertStringContainsString('@fundacja.kreatywni', $html);
        // Główny przycisk powitania prowadzi do Facebooka i zapowiada nową kartę.
        $this->assertMatchesRegularExpression('#href="https://www\.facebook\.com/profile\.php\?id=1" class="kc-btn" +target="_blank"#', $html);
    }

    public function test_wlasne_teksty_i_ukryte_sekcje(): void
    {
        $this->useTemplate(['kreatywni_texts' => [
            'hero_title' => 'Tworzymy razem',
            'offer_items' => "Origami | Składanie papieru\nFilc",
            '_hidden' => ['reviews', 'team'],
        ]]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Tworzymy razem', $html);
        $this->assertStringContainsString('Origami', $html);
        $this->assertStringContainsString('Składanie papieru', $html);
        $this->assertStringNotContainsString('Laleczki motanki', $html);
        $this->assertStringNotContainsString('id="opinie"', $html);
        $this->assertStringNotContainsString('id="zespol"', $html);
        $this->assertStringContainsString('id="oferta"', $html);
    }

    public function test_adresy_tylko_bezpieczne(): void
    {
        $this->assertNull(KreatywniTexts::href('javascript:alert(1)'));
        $this->assertNull(KreatywniTexts::href('data:text/html,x'));
        $this->assertSame('#oferta', KreatywniTexts::href('#oferta'));
        $this->assertSame('https://example.pl', KreatywniTexts::href('https://example.pl'));
        $this->assertSame('/kontakt', KreatywniTexts::href('/kontakt'));
    }

    public function test_czyszczenie_pomija_teksty_domyslne_i_liczy_ukryte_sekcje(): void
    {
        $default = KreatywniTexts::DEFS['hero_title'][3];

        $clean = KreatywniTexts::clean([
            'hero_title' => $default,
            'offer_price' => "  Cena od 40 zł  ",
            'areas' => str_replace("\n", "\r\n", KreatywniTexts::DEFS['areas'][3]),
            'nieznany' => 'x',
            '_sections' => '1',
            '_shown' => array_values(array_diff(array_keys(KreatywniTexts::SECTIONS), ['gallery'])),
        ]);

        $this->assertSame(['offer_price' => 'Cena od 40 zł', '_hidden' => ['gallery']], $clean);
        $this->assertNull(KreatywniTexts::clean(['hero_title' => $default, '_sections' => '1', '_shown' => array_keys(KreatywniTexts::SECTIONS)]));
    }

    private function settingsPayload(array $texts): array
    {
        return [
            'site_name' => 'Fundacja „Kreatywni na co dzień”', 'brand_color' => '#1b4a07', 'header_layout' => 'classic', 'site_template' => 'kreatywni',
            'content_editor' => 'tinymce', 'mail_transport' => 'default', 'contact_email' => 'kontakt@example.pl',
            'kreatywni_texts' => $texts,
        ];
    }

    public function test_panel_pokazuje_pola_i_zapisuje_teksty(): void
    {
        $this->useTemplate();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('Ustawienia szablonu: Kreatywni na co dzień')
            ->assertSee('name="kreatywni_texts[hero_title]"', false);

        $this->put(route('admin.ustawienia.update'), $this->settingsPayload([
            'hero_title' => 'Nowy nagłówek', '_sections' => '1', '_shown' => ['hero', 'offer'],
        ]))->assertSessionHasNoErrors();

        $fresh = SiteSetting::query()->find(SiteSetting::current()->id);
        $this->assertSame('Nowy nagłówek', $fresh->kreatywni_texts['hero_title']);
        $this->assertContains('mission', $fresh->kreatywni_texts['_hidden']);
        $this->assertNotContains('offer', $fresh->kreatywni_texts['_hidden']);

        $this->put(route('admin.ustawienia.update'), $this->settingsPayload(['hero_title' => 'javascript:x', 'hero_button_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('kreatywni_texts.hero_button_url');
    }

    public function test_zapis_ustawien_i_strona_dzialaja_bez_migracji(): void
    {
        $this->useTemplate();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Schema::table('site_settings', fn ($table) => $table->dropColumn('kreatywni_texts'));
        $this->resetCache();

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), $this->settingsPayload(['hero_title' => 'Bez kolumny']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->resetCache();
        $this->get('/')->assertOk()->assertSee('Warsztaty kreatywne i twórcze');
    }

    public function test_seeder_zaklada_strony_menu_i_ustawienia_tylko_dla_wskazanej_witryny(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $site = SiteSetting::current();
        $site->forceFill(['slug' => 'kreatywni', 'contact_email' => 'juz@example.pl', 'site_template' => 'default'])->save();

        putenv('SITE_SLUG=kreatywni');
        try {
            $this->seed(KreatywniSiteSeeder::class);
            $this->seed(KreatywniSiteSeeder::class); // drugi raz niczego nie dubluje
        } finally {
            putenv('SITE_SLUG');
        }

        $fresh = SiteSetting::query()->find($site->id);
        $this->assertSame('kreatywni', $fresh->site_template);
        $this->assertSame('#1b4a07', $fresh->brand_color);
        $this->assertSame('juz@example.pl', $fresh->contact_email, 'Wypełnione pole zostaje bez zmian.');
        $this->assertSame('+48 663 182 816', $fresh->contact_phone);

        $pages = Page::withoutGlobalScopes()->where('site_id', $site->id);
        $this->assertSame(1, (clone $pages)->where('title', 'Oferta')->count());
        $this->assertTrue((clone $pages)->where('title', 'Oferta')->value('is_published'));
        $this->assertFalse((bool) (clone $pages)->where('title', 'Statut Fundacji')->value('is_published'), 'Dokumenty są szkicami.');
        $this->assertSame(['Oferta', 'O nas', 'Galeria', 'Fundacja'],
            NavItem::withoutGlobalScopes()->where('site_id', $site->id)->where('location', 'main')->orderBy('order')->pluck('label')->all());
    }

    public function test_seeder_bez_wskazanej_witryny_nic_nie_zmienia(): void
    {
        putenv('SITE_SLUG');
        $before = SiteSetting::current()->site_template;

        $this->seed(KreatywniSiteSeeder::class);

        $this->resetCache();
        $this->assertSame($before, SiteSetting::current()->site_template);
        $this->assertSame(0, Page::withoutGlobalScopes()->where('title', 'Oferta')->count());
    }
}
