<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Wariant motywu FEER „FEER IS”: portal usług w układzie gov.pl z nagłówkiem i stopką FEER, per witryna. */
class FeerIsThemeTest extends TestCase
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
        $this->main->forceFill(['site_template' => 'feer'])->save();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => 'feer', 'theme_variant' => 'feer_is',
            'services_tabs' => "Dla osób|dla-osob\nDla instytucji|dla-instytucji\nBez usług|pusta",
            'services_all_label' => 'Wszystkie szkolenia', 'services_all_url' => '/szkolenia/aktualnosci',
        ]);
    }

    private function page(string $slug, array $extra = []): Page
    {
        return Page::withoutGlobalScopes()->create($extra + [
            'site_id' => $this->sub->id, 'title' => ucfirst($slug), 'slug' => $slug, 'type' => 'standard', 'is_published' => true, 'content' => '<p>x</p>',
        ]);
    }

    public function test_wariant_dziala_tylko_przy_szablonie_feer(): void
    {
        $this->assertSame('feer_is', $this->sub->themeVariant());
        $this->assertSame('default', $this->main->themeVariant());

        $this->sub->forceFill(['site_template' => 'default'])->save();
        $this->assertSame('default', $this->sub->fresh()->themeVariant());
    }

    public function test_sciezki_odbiorcow_listuja_uslugi_a_puste_sa_pomijane(): void
    {
        $osoby = $this->page('dla-osob');
        foreach (range(1, 5) as $i) {
            $this->page('usluga-'.$i, ['parent_id' => $osoby->id, 'title' => 'Usługa numer '.$i, 'meta_description' => $i === 1 ? 'Rozmowa z psychologiem.' : null]);
        }
        $instytucje = $this->page('dla-instytucji');
        $this->page('szkolenie-dostepnosci', ['parent_id' => $instytucje->id, 'title' => 'Szkolenie z dostępności']);
        $this->page('pusta');

        $html = $this->get('/szkolenia')->assertOk()
            ->assertSee('Wybierz swoją ścieżkę')->assertSee('Dla osób')->assertSee('Dla instytucji')->assertDontSee('Bez usług')
            ->assertSee('Usługa numer 1')->assertSee('Usługa numer 4')->assertSee('Rozmowa z psychologiem.')
            ->assertSee('Szkolenie z dostępności')->assertSee('Wszystkie szkolenia')
            ->getContent();

        $this->assertStringContainsString('fis-aud-col', $html);
        $this->assertStringNotContainsString('role="tablist"', $html, 'układ ścieżek: bez zakładek');
        $this->assertStringNotContainsString('feer-is-tile', $html, 'kafle zastąpione kolumnami');
        $this->assertStringNotContainsString('Usługa numer 5', $html, 'na głównej tylko cztery pierwsze usługi odbiorcy');
        $this->assertStringContainsString('Zobacz wszystko: Dla osób', $html);
        $this->assertStringContainsString('/szkolenia/dla-osob', $html, 'link odbiorcy zostaje w witrynie');
        $this->assertStringContainsString('/szkolenia/usluga-1', $html, 'linki usług zostają w witrynie');
    }

    public function test_aktualnosci_na_glownej_to_trzy_najnowsze_wpisy(): void
    {
        foreach (range(1, 6) as $i) {
            News::withoutGlobalScopes()->create([
                'site_id' => $this->sub->id, 'title' => 'Wpis numer '.$i, 'slug' => 'wpis-'.$i, 'content' => '<p>x</p>',
                'is_published' => true, 'published_at' => now()->subHours($i),
            ]);
        }

        $this->get('/szkolenia')->assertOk()->assertSee('Aktualności')->assertSee('Wpis numer 1')->assertSee('Wpis numer 3')
            ->assertDontSee('Wpis numer 4')->assertSee('Wszystkie aktualności');
    }

    public function test_witryna_glowna_ze_standardowym_feer_zostaje_bez_zmian(): void
    {
        $this->get('/')->assertOk()->assertDontSee('role="tablist"', false);
    }

    public function test_wariant_zapisuje_sie_w_panelu_i_odrzuca_nieznany(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->sub->id])
            ->get(route('admin.ustawienia.edit'))->assertOk()->assertSee('FEER IS');
    }

    public function test_wpis_w_feer_is_ma_link_powrotu_a_w_standardowym_feer_nie(): void
    {
        $news = News::withoutGlobalScopes()->create(['site_id' => $this->sub->id, 'title' => 'Wpis', 'slug' => 'wpis', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subHour()]);
        $mainNews = News::withoutGlobalScopes()->create(['site_id' => $this->main->id, 'title' => 'Wpis glowny', 'slug' => 'wpis-glowny', 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now()->subHour()]);

        $this->get('/szkolenia/aktualnosci/wpis')->assertOk()->assertSee('Powrót do aktualności');
        $this->get('/aktualnosci/wpis-glowny')->assertOk()->assertDontSee('Powrót do aktualności');
    }

    public function test_kafel_bez_ikony_ma_domyslna_ikone_dokumentu(): void
    {
        $this->sub->forceFill(['services_tabs' => 'Dla osób|dla-osob'])->save();
        $osoby = $this->page('dla-osob');
        $this->page('porada', ['parent_id' => $osoby->id, 'title' => 'Porada']);

        $html = $this->get('/szkolenia')->assertOk()->getContent();

        $this->assertStringContainsString('>description</span>', $html, 'domyślna ikona dokumentu');
    }

    public function test_ikona_zapisuje_sie_w_edytorze_strony_i_odrzuca_zla_nazwe(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = $this->page('grupy-wsparcia');
        $this->actingAs($admin)->withSession(['admin_site_chosen' => true, 'admin_active_site_id' => $this->sub->id]);

        $this->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('Ikona w siatce usług (FEER IS)');

        $payload = ['title' => 'Grupy wsparcia', 'slug' => 'grupy-wsparcia', 'type' => 'standard', 'content' => '<p>x</p>', 'is_published' => 1, 'parent_id' => null];
        $this->put(route('admin.podstrony.update', $page), $payload + ['layout_data' => ['icon' => 'bad<script>']])->assertSessionHasErrors('layout_data.icon');
        $this->put(route('admin.podstrony.update', $page), $payload + ['layout_data' => ['icon' => 'mi-groups']])->assertSessionHasNoErrors();
        $this->assertSame('mi-groups', $page->fresh()->layout_data['icon']);
    }

    /** @return array<string, mixed> */
    private function settingsPayload(): array
    {
        return [
            'site_name' => 'Szkolenia', 'brand_color' => '#c31432', 'header_layout' => 'classic', 'site_template' => 'feer',
            'content_editor' => 'tinymce', 'mail_transport' => 'default',
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
            'theme_variant' => 'feer_is', 'services_tabs' => 'Dla osób|dla-osob', 'services_all_label' => 'Wszystkie', 'services_all_url' => '/dzialania',
        ];
    }

    public function test_zapis_ustawien_zapamietuje_wariant_i_zakladki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->main->forceFill(['theme_variant' => null])->save();

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), $this->settingsPayload())->assertSessionHasNoErrors();

        $fresh = SiteSetting::query()->find($this->main->id);
        $this->assertSame('feer_is', $fresh->theme_variant);
        $this->assertSame('Dla osób|dla-osob', $fresh->services_tabs);
    }

    public function test_zapis_ustawien_nie_pada_na_serwerze_bez_migracji_wariantu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        \Illuminate\Support\Facades\Schema::table('site_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropColumn(['theme_variant', 'services_tabs', 'services_all_label', 'services_all_url']);
        });
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), $this->settingsPayload())
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/')->assertOk();
    }

    public function test_zakladka_moze_miec_ikone_w_trzecim_polu_wiersza(): void
    {
        $this->sub->forceFill(['services_tabs' => "Dla osób|dla-osob|mi-accessibility_new\nDla instytucji|dla-instytucji|zła ikona<>"])->save();

        $this->assertSame([
            ['label' => 'Dla osób', 'slug' => 'dla-osob', 'icon' => 'mi-accessibility_new'],
            ['label' => 'Dla instytucji', 'slug' => 'dla-instytucji', 'icon' => ''],
        ], $this->sub->fresh()->servicesTabs());

        $osoby = $this->page('dla-osob');
        $this->page('porada', ['parent_id' => $osoby->id, 'title' => 'Porada']);

        $this->get('/szkolenia')->assertOk()->assertSee('>accessibility_new</span>', false)->assertSee('fis-aud-col', false);
    }

    public function test_naglowek_feer_is_nie_ma_przycisku_materialy_edukacyjne_a_ma_edytowalny(): void
    {
        $this->sub->forceFill(['is_cta_label' => 'Zapisz się na szkolenie', 'is_cta_url' => '/formularz/zapisy'])->save();

        $is = $this->get('/szkolenia')->assertOk()->assertDontSee('Materiały edukacyjne')->assertSee('Zapisz się na szkolenie')->getContent();
        $this->assertStringContainsString('href="/szkolenia/formularz/zapisy"', $is, 'adres przycisku zostaje w witrynie');

        // Standardowy FEER (witryna główna) zostaje 1:1 z przyciskiem materiałów.
        $this->get('/')->assertOk()->assertSee('Materiały edukacyjne')->assertDontSee('Zapisz się na szkolenie');
    }

    public function test_bez_ustawionego_przycisku_naglowek_feer_is_nie_ma_zadnego(): void
    {
        $this->get('/szkolenia')->assertOk()->assertDontSee('Materiały edukacyjne')->assertDontSee('fa-book-open', false);
    }

    public function test_przycisk_naglowka_zapisuje_sie_w_ustawieniach(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $payload = $this->settingsPayload() + ['is_cta_label' => 'Zapisy', 'is_cta_url' => '/zapisy'];

        $this->actingAs($admin)->withSession(['admin_site_chosen' => true])->put(route('admin.ustawienia.update'), $payload)->assertSessionHasNoErrors();

        $this->assertSame('Zapisy', SiteSetting::query()->find($this->main->id)->is_cta_label);
    }
}
