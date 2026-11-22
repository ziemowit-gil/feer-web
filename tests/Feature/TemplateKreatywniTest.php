<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\NavItem;
use App\Models\Page;
use App\Models\Project;
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
        $this->assertStringContainsString('aria-roledescription="karuzela"', $html);
        $this->assertStringContainsString('Naszą misją jest', $html); // slajd 2 slidera
        $this->assertStringContainsString('data-a11y-font="up"', $html); // pasek dostępności na górze
        $this->assertStringContainsString('<span class="kc-lbl-short">Dostępność</span>', $html); // krótka etykieta na telefonie
        $this->assertStringContainsString('W czym pomagamy', $html);
        $this->assertStringContainsString('Laleczki motanki', $html);
        $this->assertStringContainsString('<strong>od 30 zł</strong> <span>za osobę</span>', $html);
        $this->assertStringContainsString('Co nas wyróżnia?', $html);
        $this->assertStringNotContainsString('id="zespol"', $html, 'Zespół jest domyślnie ukryty (jest na stronie O nas).');
        $this->assertStringContainsString('<span class="kc-review-name">Justyna</span>', $html);
        $this->assertStringContainsString('class="kc-price-card kc-dark"', $html); // karta cenowa oferty
        $this->assertStringContainsString('Boże Narodzenie', $html); // kolekcja warsztatów
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

        $this->assertStringContainsString('id="social" class="kc-sec kc-sec-cream"', $html, 'Social media na jasnym tle.');
        $this->assertStringContainsString('@fundacja.kreatywni', $html);
        // Główny przycisk powitania prowadzi do Facebooka i zapowiada nową kartę.
        $this->assertMatchesRegularExpression('#href="https://www\.facebook\.com/profile\.php\?id=1" class="kc-btn" +target="_blank"#', $html);
    }

    public function test_wlasne_teksty_i_ukryte_sekcje(): void
    {
        $this->useTemplate(['kreatywni_texts' => [
            'hero_title' => 'Tworzymy razem',
            'offer_items' => "Origami | Zimowe warsztaty\nFilc",
            '_hidden' => ['reviews', 'team'],
        ]]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Tworzymy razem', $html);
        $this->assertStringContainsString('Origami', $html);
        $this->assertStringContainsString('Zimowe warsztaty', $html, 'Druga część wiersza to grupa (kolekcja) warsztatów.');
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
        $allButTeam = array_values(array_diff(array_keys(KreatywniTexts::SECTIONS), KreatywniTexts::DEFAULT_HIDDEN));
        $this->assertNull(KreatywniTexts::clean(['hero_title' => $default, '_sections' => '1', '_shown' => $allButTeam]));
        $this->assertSame(['_hidden' => []], KreatywniTexts::clean(['_sections' => '1', '_shown' => array_keys(KreatywniTexts::SECTIONS)]));
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
            ->assertSee('name="kreatywni_texts[hero_title]"', false)
            ->assertSee('karta=stopka', false)->assertSee('Ogólne → Stopka')
            ->assertDontSee('(szablony FEER i Kreatywni)');

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

    public function test_mega_menu_ma_sekcje_strony_z_kotwicami_a_podstrona_wlasny_uklad_bez_spisu(): void
    {
        $this->useTemplate();
        Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'standard', 'is_published' => true, 'meta_description' => 'Warsztaty z dojazdem.',
            'content' => '<h2>Warsztaty tematyczne</h2><h3>Laleczki motanki</h3><p>Opis</p><h3>Perfumy</h3><p>Opis</p><h2>Zamów warsztaty</h2><p>Napisz.</p>']);
        NavItem::create(['label' => 'Oferta', 'url' => '/oferta', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);
        NavItem::create(['label' => 'Galeria', 'url' => '/galeria', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 2]);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('id="kc-mega-0"', $home);
        $this->assertStringContainsString('aria-controls="kc-mega-0"', $home);
        $this->assertStringContainsString('href="/oferta#laleczki-motanki"', $home);
        $this->assertStringContainsString('class="kc-mega-head">Zamów warsztaty</a>', $home);
        $this->assertStringNotContainsString('kc-mega-1', $home, 'Pozycja bez podmenu zostaje zwykłym linkiem.');

        $page = $this->get('/oferta')->assertOk()->getContent();
        $this->assertStringContainsString('class="kc-page-head"', $page);
        $this->assertStringContainsString('class="kc kc-crumbs"', $page, 'Okruszki w kremowym pasie szablonu.');
        $this->assertStringContainsString('id="laleczki-motanki"', $page);
        $this->assertStringNotContainsString('Na tej stronie', $page);
        $this->assertStringContainsString('Bądźmy w kontakcie', $page);
        $this->assertStringContainsString('class="kc-price-card kc-dark"', $page, 'Strona Oferta ma na górze blok sprzedażowy.');
    }

    public function test_dedykowana_strona_o_nas_i_jej_sekcje_w_mega_menu(): void
    {
        $this->useTemplate();
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true, 'content' => '']);
        NavItem::create(['label' => 'O nas', 'url' => '/o-nas', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('href="/o-nas#zespol"', $home);
        $this->assertStringContainsString('href="/o-nas#dolacz"', $home);

        $html = $this->get('/o-nas')->assertOk()->getContent();
        $this->assertStringContainsString('id="zespol"', $html);
        $this->assertStringContainsString('Anna Mossór-Koper', $html);
        $this->assertStringContainsString('Dlaczego powstała Fundacja?', $html);
        $this->assertStringContainsString('Aby chronić pszczoły, lasy i rzeki.', $html);
        $this->assertStringContainsString('id="historia"', $html);
        $this->assertStringContainsString('id="dolacz"', $html);
        $this->assertStringContainsString('Poznajmy się', $html);
        $this->assertStringNotContainsString('Bądźmy w kontakcie', $html, 'Strona O nas ma własne zaproszenie zamiast ogólnego pasa.');
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_slider_ma_slajd_z_zaproszeniem_do_social_media(): void
    {
        $this->useTemplate(['facebook_url' => 'https://www.facebook.com/profile.php?id=1', 'instagram_url' => 'https://www.instagram.com/fundacja.kreatywni/']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="3 z 3"', $html);
        $this->assertStringContainsString('Obserwuj na Facebooku', $html);
        $this->assertStringContainsString('Obserwuj na Instagramie', $html);
        $this->assertStringContainsString('class="kc-net-pill"', $html, 'Profile wyróżnione w belce.');
    }

    public function test_kontakt_ma_wlasny_uklad_bez_danych_domyslnych_innych_szablonow(): void
    {
        $this->useTemplate(['contact_phone' => '+48 663 182 816', 'contact_email' => 'warsztaty@example.pl', 'contact_address' => '', 'contact_city' => 'Kraków i okolice']);

        $html = $this->followingRedirects()->get('/kontakt')->assertOk()->getContent();

        $this->assertStringContainsString('class="kc-page-head"', $html);
        $this->assertStringContainsString('href="mailto:warsztaty@example.pl"', $html);
        $this->assertStringContainsString('Gdzie działamy', $html);
        $this->assertStringNotContainsString('Pracujemy zdalnie', $html);
    }

    /** Działanie w module Działania (kategoria jest wymagana). */
    private function project(string $title, array $extra = []): Project
    {
        $category = Category::query()->firstOrCreate(['slug' => 'warsztaty'], ['name' => 'Warsztaty']);

        return Project::create(array_merge(['title' => $title, 'slug' => \Illuminate\Support\Str::slug($title), 'category_id' => $category->id,
            'is_published' => true, 'is_completed' => false], $extra));
    }

    public function test_sekcja_dzialan_pokazuje_zaznaczone_a_bez_nich_ostatnie_trwajace(): void
    {
        $this->useTemplate();
        $make = fn (string $title, array $extra = []) => $this->project($title, ['excerpt' => 'Opis '.$title] + $extra);

        $this->get('/')->assertOk()->assertDontSee('kc-projects-title', false);

        $make('Warsztaty w szpitalu');
        $make('Zakończona akcja', ['is_completed' => true]);
        $make('Szkic działania', ['is_published' => false]);

        $this->get('/')->assertOk()
            ->assertSee('Nasze działania')->assertSee('Warsztaty w szpitalu')->assertSee('Opis Warsztaty w szpitalu')
            ->assertSee('Wszystkie działania')->assertSee(route('projects.index'), false)
            ->assertDontSee('Zakończona akcja')->assertDontSee('Szkic działania');

        // Zaznaczone „Pokaż na stronie głównej” mają pierwszeństwo przed ostatnio dodanymi.
        $make('Dary dla DPS', ['show_on_home' => true]);
        $this->get('/')->assertOk()->assertSee('Dary dla DPS')->assertDontSee('Warsztaty w szpitalu');
    }

    public function test_sekcja_dzialan_znika_po_ukryciu_albo_wylaczeniu_modulu(): void
    {
        $site = $this->useTemplate(['kreatywni_texts' => ['_hidden' => ['team', 'projects']]]);
        $this->project('Akcja zimowa');

        $this->get('/')->assertOk()->assertDontSee('Akcja zimowa');

        $site->forceFill(['kreatywni_texts' => null, 'disabled_modules' => ['projects']])->save();
        $this->resetCache();
        $this->get('/')->assertOk()->assertDontSee('Akcja zimowa');
    }

    public function test_lista_dzialan_ma_uklad_szablonu_karty_i_link_do_zakonczonych(): void
    {
        $this->useTemplate(['kreatywni_texts' => ['projects_title' => 'Co robimy']]);
        $this->project('Warsztaty w DPS', ['excerpt' => 'Spotkania z seniorami.']);
        $this->project('Stara akcja', ['is_completed' => true]);

        $this->get(route('projects.index'))->assertOk()
            ->assertSee('kc-page-head', false)->assertSee('<h1 id="kc-page-title">Co robimy</h1>', false)
            ->assertSee('<h2 class="kc-child-title">Warsztaty w DPS</h2>', false)->assertSee('Spotkania z seniorami.')
            ->assertSee(route('projects.show', 'warsztaty-w-dps'), false)
            ->assertDontSee('Stara akcja')->assertSee('Zakończone działania')->assertSee(route('projects.archive'), false);

        $this->get(route('projects.show', 'warsztaty-w-dps'))->assertOk()->assertSee('Warsztaty w DPS');
    }


    public function test_stopka_bez_podwojnego_copyright_z_podpisem_wykonawcy_i_ikonka_wecms(): void
    {
        $site = $this->useTemplate(['site_name' => 'Fundacja Testowa']);
        $year = now()->year;

        $this->get('/')->assertOk()
            ->assertSee('© '.$year.' Fundacja Testowa · Wszystkie prawa zastrzeżone')
            ->assertDontSee('· © '.$year, false)
            ->assertSee('<span class="wecms-mark">', false)
            ->assertSee('<a href="https://ziemowitgil.pl" target="_blank" rel="noopener">Projekt i wykonanie: Ziemowit Gil', false)
            ->assertSee('Mapa serwisu')->assertSee('Panel administracyjny');

        // Własny tekst praw i wyłączone linki z Ustawienia → Ogólne → Stopka; podpis CMS wyłączony (tylko na feer.org.pl).
        $site->forceFill(['footer_options' => ['copyright' => 'Działamy od 2020 roku', 'show_sitemap' => false, 'show_admin_link' => false], 'show_cms_credit' => false])->save();
        $this->resetCache();

        $this->get('/')->assertOk()
            ->assertSee('© '.$year.' Fundacja Testowa · Działamy od 2020 roku')
            ->assertDontSee('Mapa serwisu')->assertDontSee('Panel administracyjny')
            ->assertDontSee('Projekt i wykonanie: Ziemowit Gil')->assertDontSee('<span class="wecms-mark">', false);
    }

    public function test_seeder_zaklada_strony_menu_i_ustawienia_tylko_dla_wskazanej_witryny(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $site = SiteSetting::current();
        $site->forceFill(['slug' => 'kreatywni', 'contact_email' => 'juz@example.pl', 'contact_address' => 'ul. Warszawska 12', 'site_template' => 'default'])->save();

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
        $this->assertSame('', (string) $fresh->contact_address, 'Domyślny adres FEER z migracji traktujemy jak puste pole.');

        $pages = Page::withoutGlobalScopes()->where('site_id', $site->id);
        $this->assertSame(1, (clone $pages)->where('title', 'Oferta')->count());
        $this->assertTrue((clone $pages)->where('title', 'Oferta')->value('is_published'));
        $this->assertFalse((bool) (clone $pages)->where('title', 'Statut Fundacji')->value('is_published'), 'Dokumenty są szkicami.');
        $this->assertFalse($fresh->footerOptions()['staff_link'], 'Bez linku do strefy współpracownika FEER.');
        $this->assertSame(['Oferta', 'Działania', 'O nas', 'Galeria', 'Fundacja'],
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
