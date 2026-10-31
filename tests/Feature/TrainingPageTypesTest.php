<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Typy stron instytucji szkoleniowej, moduły szkoleniowe „FEER IS”, dziedziczenie slidera i misji oraz link powrotu. */
class TrainingPageTypesTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $this->main = SiteSetting::current();
        $this->main->forceFill(['site_name' => 'Fundacja Glowna', 'site_template' => 'feer', 'hero_single' => false, 'hero_mission_slide' => true, 'tagline' => 'Misja fundacji testowej'])->save();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is', 'hero_single' => false,
        ]);
    }

    private function page(string $slug, string $type, array $td = [], array $extra = []): Page
    {
        return Page::withoutGlobalScopes()->create($extra + [
            'site_id' => $this->sub->id, 'title' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, 'type' => $type,
            'is_published' => true, 'content' => '<p>Treść</p>', 'type_data' => $td,
        ]);
    }

    public function test_typy_sa_zarejestrowane_w_grupie_oferta_i_edukacja(): void
    {
        foreach (['training_offer', 'training_catalog', 'trainer_profile', 'training_accreditations'] as $type) {
            $this->assertArrayHasKey($type, Page::types(), $type);
        }
    }

    public function test_szkolenie_pokazuje_program_terminy_cene_i_zapisy(): void
    {
        $this->page('dostepnosc-cyfrowa', 'training_offer', [
            'lead' => 'Nauczysz się tworzyć dostępne dokumenty.', 'duration' => '16 godzin', 'mode' => 'online', 'price' => '480 zł',
            'cta_label' => 'Zapisz się', 'cta_url' => '/formularz/zapisy', 'outcomes' => [['text' => 'Poprawisz plik PDF']],
            'program' => [['title' => 'Moduł pierwszy', 'hours' => '4', 'text' => 'Wstęp']],
            'editions' => [['date' => now()->addMonth()->toDateString(), 'place' => 'Kraków', 'status' => 'few']],
            'trainers' => [['name' => 'Jan Kowalski', 'role' => 'Trener']],
        ]);

        $this->get('/szkolenia/dostepnosc-cyfrowa')->assertOk()
            ->assertSee('16 godzin')->assertSee('Online')->assertSee('480 zł')->assertSee('Zapisz się')
            ->assertSee('Poprawisz plik PDF')->assertSee('Moduł pierwszy')->assertSee('Kraków')->assertSee('Ostatnie miejsca')
            ->assertSee('Jan Kowalski')->assertSee('"@type":"Course"', false);
    }

    public function test_katalog_listuje_szkolenia_z_podstron_filtruje_kategorie_i_ma_slider_wyroznionych(): void
    {
        $catalog = $this->page('katalog', 'training_catalog', ['show_slider' => '1']);
        $this->page('a', 'training_offer', ['category' => 'Dostępność', 'featured' => '1', 'lead' => 'Lead A'], ['parent_id' => $catalog->id, 'title' => 'Szkolenie A']);
        $this->page('b', 'training_offer', ['category' => 'Komputer'], ['parent_id' => $catalog->id, 'title' => 'Szkolenie B']);

        $this->get('/szkolenia/katalog')->assertOk()->assertSee('Szkolenie A')->assertSee('Szkolenie B')
            ->assertSee('Wyróżnione szkolenia');
        // Filtr zawęża listę kart (slider wyróżnionych zostaje bez zmian).
        $filtered = $this->get('/szkolenia/katalog?kategoria=Komputer')->assertOk()->assertSee('Szkolenie B')->assertSee('aria-current="true"', false)->getContent();
        $this->assertSame(0, substr_count($filtered, 'stretched-link text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none">Szkolenie A'));
    }

    public function test_trener_pokazuje_swoje_szkolenia_a_akredytacje_wpisy_i_wskazniki(): void
    {
        $this->page('jan-kowalski', 'trainer_profile', ['role' => 'Trener dostępności', 'specializations' => [['text' => 'PDF']]], ['title' => 'Jan Kowalski']);
        $this->page('szkolenie-x', 'training_offer', ['trainers' => [['name' => 'jan kowalski']]], ['title' => 'Szkolenie X']);
        $this->page('akredytacje', 'training_accreditations', [
            'accreditations' => [['name' => 'Wpis do RIS', 'issuer' => 'WUP', 'number' => '12/2026', 'valid_until' => now()->addYear()->toDateString()]],
            'quality_stats' => [['value' => '4,8/5', 'label' => 'średnia ocena']],
        ]);

        $this->get('/szkolenia/jan-kowalski')->assertOk()->assertSee('Trener dostępności')->assertSee('PDF')->assertSee('Szkolenie X');
        $this->get('/szkolenia/akredytacje')->assertOk()->assertSee('Wpis do RIS')->assertSee('WUP')->assertSee('4,8/5')->assertSee('Ważne do');
    }

    public function test_feer_is_nie_ma_nasze_dzialania_a_ma_moduly_szkoleniowe(): void
    {
        $catalog = $this->page('katalog', 'training_catalog');
        $this->page('a', 'training_offer', [
            'category' => 'Dostępność', 'duration' => '8 godzin', 'editions' => [['date' => now()->addDays(10)->toDateString(), 'place' => 'Nowy Sącz']],
        ], ['parent_id' => $catalog->id, 'title' => 'Szkolenie Alfa']);

        $html = $this->get('/szkolenia')->assertOk()
            ->assertSee('Szkolenia')->assertSee('Szkolenie Alfa')->assertSee('Najbliższe terminy')->assertSee('Nowy Sącz')
            ->assertSee('Katalog szkoleń')->assertDontSee('Nasze działania')->getContent();

        $this->assertStringContainsString('/szkolenia/katalog', $html);
    }

    public function test_slider_i_misja_sa_dziedziczone_z_witryny_glownej(): void
    {
        HeroSlide::withoutGlobalScopes()->create(['site_id' => $this->main->id, 'title' => 'Slajd glownej fundacji', 'text' => 'x', 'order' => 1]);

        $this->get('/szkolenia')->assertOk()->assertSee('Slajd glownej fundacji')->assertSee('Misja fundacji testowej');

        HeroSlide::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'title' => 'Slajd wlasny szkolen', 'text' => 'x', 'order' => 1]);

        $own = $this->get('/szkolenia')->assertOk()->assertSee('Slajd wlasny szkolen')->assertDontSee('Slajd glownej fundacji');
        $own->assertSee('Misja fundacji testowej');
    }

    public function test_link_powrotu_do_portalu_jest_zawsze_na_witrynie_w_sieci_i_edytowalny(): void
    {
        $this->get('/szkolenia')->assertOk()->assertSee('Powrót do portalu Fundacji');
        $this->get('/')->assertOk()->assertDontSee('Powrót do portalu Fundacji');

        $this->sub->forceFill(['parent_link_label' => 'Wróć na stronę Fundacji', 'parent_link_url' => 'https://feer.example.test'])->save();

        $this->get('/szkolenia')->assertOk()->assertSee('Wróć na stronę Fundacji')->assertSee('https://feer.example.test', false)->assertDontSee('Powrót do portalu Fundacji');
    }

    public function test_pola_linku_powrotu_zapisuja_sie_w_panelu_witryny_w_sieci(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payload = [
            'site_name' => 'Szkolenia', 'brand_color' => '#c31432', 'header_layout' => 'classic', 'site_template' => 'feer',
            'content_editor' => 'tinymce', 'mail_transport' => 'default',
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
            'parent_link_label' => 'Do portalu', 'parent_link_url' => 'https://feer.example.test',
        ];

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->sub->id])
            ->get(route('admin.ustawienia.edit'))->assertOk()->assertSee('Link powrotu do portalu Fundacji');
        $this->put(route('admin.ustawienia.update'), $payload)->assertSessionHasNoErrors();

        $this->assertSame('Do portalu', $this->sub->fresh()->parent_link_label);
    }

    public function test_w_feer_is_dzialania_nazywaja_sie_nasze_szkolenia(): void
    {
        $this->assertSame('Nasze szkolenia', $this->sub->projectsLabel());
        $this->assertSame('Nasze działania', $this->main->projectsLabel());
    }
}
