<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AccessibilityPictograms;
use App\Support\BlockRenderer;
use App\Support\EditorPermissions;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Piktogramy dostępności (Fundacja Kultury Bez Barier, CC BY-SA 4.0): blok edytora i wstawka [piktogramy:…]. */
class AccessibilityPictogramsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_kazdy_piktogram_ma_plik_svg_licencje_i_nazwe(): void
    {
        $dir = public_path('img/piktogramy-dostepnosci');
        $this->assertCount(22, AccessibilityPictograms::ALL);
        foreach (AccessibilityPictograms::ALL as $key => $name) {
            $this->assertFileExists($dir.'/'.$key.'.svg');
            $this->assertNotSame('', trim($name));
        }
        $this->assertFileExists($dir.'/LICENSE.txt');
        $this->assertStringContainsString('Tomasz Grabowski', file_get_contents($dir.'/README.md'));
        $this->assertStringContainsString('CC BY-SA 4.0', file_get_contents($dir.'/README.md'));
    }

    public function test_blok_z_nazwami_ma_pusty_alt_i_podpis_autora(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'pictograms', 'attrs' => ['title' => 'Udogodnienia', 'level' => 3, 'items' => [
                ['key' => 'audiodeskrypcja'], ['key' => 'tlumaczenie-na-pjm', 'label' => 'PJM w <b>sobotę</b>'], ['key' => 'audiodeskrypcja'], ['key' => 'nieznany'],
            ]]],
        ]);

        $this->assertStringContainsString('<h3 class="content-pictograms-title">Udogodnienia</h3>', $html);
        $this->assertStringContainsString('<img class="content-pictogram-image" src="/img/piktogramy-dostepnosci/audiodeskrypcja.svg" alt="" width="48" height="48"', $html);
        $this->assertStringContainsString('<span class="content-pictogram-label">Audiodeskrypcja</span>', $html);
        $this->assertStringContainsString('<span class="content-pictogram-label">PJM w sobotę</span>', $html);
        // Bez powtórzeń i nieznanych kluczy.
        $this->assertSame(2, substr_count($html, '<li class="content-pictogram">'));
        $this->assertStringContainsString('Tomasz Grabowski', $html);
        $this->assertStringContainsString('href="https://creativecommons.org/licenses/by-sa/4.0/legalcode.pl"', $html);
    }

    public function test_blok_bez_nazw_ma_tekst_alternatywny(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'pictograms', 'attrs' => ['labels' => false, 'size' => 'large', 'items' => [['key' => 'petla-indukcyjna'], ['key' => 'etr', 'label' => 'Informacja w ETR']]]],
        ]);

        $this->assertStringContainsString('content-pictograms--large content-pictograms--icons', $html);
        $this->assertStringContainsString('alt="Pętla indukcyjna" width="64" height="64"', $html);
        $this->assertStringContainsString('alt="Informacja w ETR"', $html);
        $this->assertStringNotContainsString('content-pictogram-label', $html);
        $this->assertStringNotContainsString('alt=""', $html);
    }

    public function test_pusty_blok_nic_nie_pokazuje(): void
    {
        $this->assertSame('', BlockRenderer::render([['type' => 'pictograms', 'attrs' => ['title' => 'Nic', 'items' => [['key' => '../etc/passwd']]]]]));
    }

    public function test_wstawka_w_klasycznej_tresci(): void
    {
        $this->assertTrue(ShortcodeParser::has('<p>[piktogramy:etr, napisy-polskie]</p>'));
        $html = ShortcodeParser::render('<p>Przed</p><p>[piktogramy:etr, napisy-polskie]</p>');
        $this->assertStringContainsString('<span class="content-pictogram-label">Tekst łatwy do czytania (ETR)</span>', $html);
        $this->assertStringContainsString('napisy-polskie.svg', $html);
        $this->assertStringNotContainsString('[piktogramy', $html);

        $icons = ShortcodeParser::render('[piktogramy:system-fm,bez-podpisow]');
        $this->assertStringContainsString('alt="System FM"', $icons);
    }

    public function test_blok_jest_w_uprawnieniach_grup(): void
    {
        $this->assertArrayHasKey('pictograms', EditorPermissions::blockTypeOptions());
    }

    public function test_admin_zapisuje_blok_i_widac_go_na_stronie(): void
    {
        $page = Page::create(['title' => 'Dostępność', 'slug' => 'dostepnosc-wydarzenia', 'content' => '<p>Stara</p>', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->putJson(route('admin.podstrony.bloki.update', $page), [
                'title' => 'Dostępność',
                'blocks' => [['id' => 'b1', 'type' => 'pictograms', 'attrs' => ['items' => [['key' => 'miejsce-wyciszenia']]]]],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $page->refresh();
        $this->assertSame('pictograms', $page->blocks[0]['type']);

        $html = $this->get('/dostepnosc-wydarzenia')->assertOk()->getContent();
        $this->assertStringContainsString('/img/piktogramy-dostepnosci/miejsce-wyciszenia.svg', $html);
        $this->assertStringContainsString('<span class="content-pictogram-label">Miejsce wyciszenia</span>', $html);
    }
}
