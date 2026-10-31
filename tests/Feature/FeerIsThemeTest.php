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

    public function test_zakladki_to_podstrony_wskazanych_stron_a_puste_sa_pomijane(): void
    {
        $osoby = $this->page('dla-osob');
        $this->page('wsparcie-psychologiczne', ['parent_id' => $osoby->id, 'title' => 'Wsparcie psychologiczne', 'meta_description' => 'Rozmowa z psychologiem.']);
        $instytucje = $this->page('dla-instytucji');
        $this->page('szkolenie-dostepnosci', ['parent_id' => $instytucje->id, 'title' => 'Szkolenie z dostępności']);
        $this->page('pusta');

        $html = $this->get('/szkolenia')->assertOk()
            ->assertSee('Dla osób')->assertSee('Dla instytucji')->assertDontSee('Bez usług')
            ->assertSee('Wsparcie psychologiczne')->assertSee('Rozmowa z psychologiem.')
            ->assertSee('Szkolenie z dostępności')->assertSee('Wszystkie szkolenia')
            ->getContent();

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('/szkolenia/wsparcie-psychologiczne', $html, 'linki usług zostają w witrynie');
    }

    public function test_aktualnosci_w_ukladzie_wyrozniony_wpis_i_lista(): void
    {
        foreach (range(1, 6) as $i) {
            News::withoutGlobalScopes()->create([
                'site_id' => $this->sub->id, 'title' => 'Wpis numer '.$i, 'slug' => 'wpis-'.$i, 'content' => '<p>x</p>',
                'is_published' => true, 'published_at' => now()->subHours($i),
            ]);
        }

        $this->get('/szkolenia')->assertOk()->assertSee('Aktualności')->assertSee('Wpis numer 1')->assertSee('Wpis numer 6');
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
}
