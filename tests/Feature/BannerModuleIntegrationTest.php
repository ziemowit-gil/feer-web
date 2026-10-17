<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\BannerZone;
use App\Models\EducationalMaterial;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ClassicInserts;
use App\Support\MaterialList;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Integracja treści z modułem Banery (/edycja/banery, /edycja/strefy-bannerow): shortcody [banery:strefa]
 * i [baner:ID] w obu edytorach, diagnoza modułów w bloku „Shortcode”, publikacja siatki kafelków,
 * filtr strefy na liście banerów i lista materiałów [materialy…].
 */
class BannerModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function zoneWithBanners(): array
    {
        $zone = BannerZone::create(['slug' => 'na-skroty', 'label' => 'Na skróty', 'max_concurrent' => 5]);
        $a = Banner::create(['name' => 'Paszporty', 'type' => 'image', 'image_path' => 'banery/paszport.webp', 'image_alt' => 'Zarezerwuj termin w punkcie paszportowym', 'width' => 2400, 'height' => 240, 'link_url' => 'https://example.pl/paszport', 'link_target' => '_self', 'is_active' => true]);
        $a->forceFill(['impressions' => 100, 'clicks' => 7])->save(); // liczniki nie są w $fillable
        $b = Banner::create(['name' => 'Poradnik', 'type' => 'image', 'image_path' => 'banery/poradnik.webp', 'image_alt' => 'Poradnik bezpieczeństwa', 'link_url' => '/poradnik', 'is_active' => true]);
        $off = Banner::create(['name' => 'Stary', 'type' => 'image', 'image_path' => 'banery/stary.webp', 'image_alt' => 'Stary', 'link_url' => '/x', 'is_active' => false]);
        $zone->banners()->attach([$a->id => ['priority' => 10], $b->id => ['priority' => 5], $off->id => ['priority' => 99]]);

        return [$zone, $a, $b, $off];
    }

    public function test_strefa_banerow_w_tresci_w_kolejnosci_priorytetu_z_etykieta_strefy(): void
    {
        [, $a, $b] = $this->zoneWithBanners();

        $html = ShortcodeParser::render('<p>[banery:na-skroty]</p>');

        $this->assertStringContainsString('role="region" aria-label="Na skróty"', $html);
        $this->assertStringNotContainsString('<p>[banery', $html);
        $this->assertLessThan(strpos($html, 'Poradnik bezpieczeństwa'), strpos($html, 'Zarezerwuj termin'));
        $this->assertStringContainsString(route('banner.click', $a), $html); // kliknięcia liczone w module
        $this->assertStringContainsString('width="2400"', $html);
        $this->assertStringNotContainsString('Stary', $html);
    }

    public function test_pojedynczy_baner_tylko_gdy_aktywny_i_w_oknie_czasowym(): void
    {
        [, $a, , $off] = $this->zoneWithBanners();

        $this->assertStringContainsString('alt="Zarezerwuj termin w punkcie paszportowym"', ShortcodeParser::render('[baner:'.$a->id.']'));
        $this->assertSame('', trim(ShortcodeParser::render('<p>[baner:'.$off->id.']</p>')));
        $a->update(['starts_at' => now()->addDay()]);
        $this->assertSame('', trim(ShortcodeParser::render('[baner:'.$a->id.']')));
        $this->assertTrue(ShortcodeParser::has('[banery:na-skroty]'));
    }

    public function test_dane_edytorow_maja_strefy_banery_i_diagnoze_ze_statystykami(): void
    {
        [$zone, $a, , $off] = $this->zoneWithBanners();
        $draft = Page::create(['title' => 'Dla kursantów', 'slug' => 'dla-kursantow', 'type' => 'tiles_grid', 'is_published' => false, 'content' => '', 'tiles' => [['label' => 'A', 'url' => '/a']]]);

        $ci = ClassicInserts::forBlockEditor();

        $this->assertContains(['value' => 'na-skroty', 'label' => 'Na skróty', 'count' => 2], $ci['bannerZones']);
        $this->assertContains(['value' => $off->id, 'label' => 'Stary (wyłączony)'], $ci['banners']);
        $this->assertSame(['impressions' => 100, 'clicks' => 7], $ci['diagnostics']['[baner:'.$a->id.']']['stats']);
        $this->assertSame(route('admin.banery.index', ['strefa' => 'na-skroty']), $ci['diagnostics']['[banery:na-skroty]']['editUrl']);
        $this->assertSame('disabled', $ci['diagnostics']['[baner:'.$off->id.']']['state']);
        $this->assertSame('draft', $ci['diagnostics']['[kafelki:dla-kursantow]']['state']);
        $this->assertSame(route('admin.edytor-blokowy.publish', $draft), $ci['diagnostics']['[kafelki:dla-kursantow]']['publishUrl']);
    }

    public function test_opublikowanie_siatki_kafelkow_z_edytora(): void
    {
        $grid = Page::create(['title' => 'Siatka', 'slug' => 'siatka', 'type' => 'tiles_grid', 'is_published' => false, 'content' => '']);
        $plain = Page::create(['title' => 'Zwykła', 'slug' => 'zwykla', 'is_published' => false, 'content' => '']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertFalse($this->post(route('admin.edytor-blokowy.publish', $grid))->isOk());
        $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.publish', $plain))->assertNotFound();
        $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.publish', $grid))->assertOk()->assertJson(['ok' => true]);
        $this->assertTrue($grid->fresh()->is_published);
    }

    public function test_lista_banerow_z_filtrem_strefy(): void
    {
        $this->zoneWithBanners();
        Banner::create(['name' => 'Poza strefą', 'type' => 'image', 'is_active' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.banery.index', ['strefa' => 'na-skroty']))
            ->assertOk()->assertSee('Banery w strefie')->assertSee('Paszporty')->assertDontSee('Poza strefą');
        $this->actingAs($admin)->get(route('admin.banery.index'))->assertOk()->assertSee('Poza strefą');
    }

    public function test_lista_materialow_w_tresci(): void
    {
        EducationalMaterial::create(['title' => 'Komiks PDF', 'description' => 'd', 'type' => 'pdf', 'is_published' => true, 'target_group' => 'nauczyciele']);
        EducationalMaterial::create(['title' => 'Webinar', 'description' => 'd', 'type' => 'video', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_published' => true, 'target_group' => 'nauczyciele']);
        EducationalMaterial::create(['title' => 'Dla programistów', 'description' => 'd', 'type' => 'pdf', 'is_published' => true, 'target_group' => 'programisci']);

        $html = ShortcodeParser::render('<p>[materialy:grupa=nauczyciele;typ=video]</p>');
        $this->assertStringContainsString('Webinar<span class="sr-only">', $html);
        $this->assertStringNotContainsString('Komiks PDF', $html);
        $this->assertStringNotContainsString('Dla programistów', $html);
        $this->assertStringContainsString('Wszystkie materiały edukacyjne', $html);
        $this->assertSame(1, substr_count($html, '<dialog data-material-preview-dialog'));

        $this->assertSame('[materialy:grupa=nauczyciele;liczba=3]', MaterialList::shortcode(['group' => 'nauczyciele', 'type' => 'zly', 'limit' => 3]));
        $this->assertSame('', ShortcodeParser::render('[materialy:grupa=graficy]'));
    }

    public function test_blok_materialow_renderuje_shortcode_i_karty(): void
    {
        EducationalMaterial::create(['title' => 'Scenariusz lekcji', 'description' => 'd', 'type' => 'scenariusz', 'is_published' => true, 'target_group' => 'nauczyciele']);

        $content = \App\Support\BlockRenderer::render([['type' => 'materials', 'attrs' => ['group' => 'nauczyciele', 'type' => 'nieznany', 'limit' => 99]]]);
        $this->assertSame('<p>[materialy:grupa=nauczyciele;liczba=24]</p>', $content);
        $this->assertStringContainsString('Scenariusz lekcji', ShortcodeParser::render($content));
    }

    public function test_strefa_strona_glowna_na_stronie_glownej_feer(): void
    {
        $this->artisan('migrate')->assertSuccessful();
        $zone = BannerZone::where('slug', 'home')->firstOrFail();
        $banner = Banner::create(['name' => 'Kalendarz', 'type' => 'image', 'image_path' => 'banery/kal.webp', 'image_alt' => 'Dolnośląski kalendarz badań profilaktycznych', 'link_url' => '/badania', 'is_active' => true]);
        $zone->banners()->attach($banner->id, ['priority' => 1]);
        SiteSetting::current()->update(['site_template' => 'feer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->get('/')->assertOk()
            ->assertSee('data-zone="home"', false)
            ->assertSee('aria-label="Strona główna (pod skrótami)"', false)
            ->assertSee('alt="Dolnośląski kalendarz badań profilaktycznych"', false);
    }

    public function test_klasyczny_edytor_i_edytor_blokowy_wstawiaja_banery_i_materialy(): void
    {
        $this->zoneWithBanners();
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>x</p>', 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();
        $this->assertStringContainsString('Wstaw baner lub materiały', $html);
        $this->assertStringContainsString('<option value="[banery:na-skroty]">Na skróty (2)</option>', $html);
        $this->assertStringContainsString('<option value="[materialy:grupa=nauczyciele]">Nauczyciele</option>', $html);
        $this->assertStringContainsString("text: 'Baner sekcji (tytuł)'", $html);

        $js = file_get_contents(resource_path('js/block-editor.js'));
        foreach (["bannerzone: { as: 'shortcode', title: 'Zestaw banerów'", "modbanner: { as: 'shortcode', title: 'Baner z modułu'", "materials: { title: 'Materiały edukacyjne'", "' Zarządzaj strefą'", "'Opublikuj siatkę'", 'Wyświetlenia: ', "title: 'Na skróty — banery'", "title: 'Media społecznościowe'", "title: 'Materiały dla grupy'"] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
    }
}
