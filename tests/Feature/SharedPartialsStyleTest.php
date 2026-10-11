<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wspólne partiale stron (menu działu w każdym stylu, zakładki, spis treści,
 * „W tym dziale", galeria, pliki do pobrania) w płaskim stylu serwisu:
 * bez obramowań, linii podziału i plakietek, tylko kolory firmowe, tekst nigdy
 * w kolorze text-brand (#1e6dff na jasnym tle ma za mały kontrast).
 */
class SharedPartialsStyleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    /** Dział: strona nadrzędna z dwiema opublikowanymi podstronami i wnukiem. */
    private function section(?string $style = null, bool $sideNav = true): Page
    {
        $parent = Page::create([
            'title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true,
            'show_side_nav' => $sideNav, 'side_nav_style' => $style ?? 'sidebar',
            'content' => '<h2>Pierwsza sekcja</h2><p>a</p><h2>Druga sekcja</h2><p>b</p><h3>Podsekcja</h3><p>c</p>',
        ]);
        $a = Page::create(['title' => 'Podstrona A', 'slug' => 'podstrona-a', 'type' => 'standard', 'is_published' => true, 'parent_id' => $parent->id, 'order' => 1, 'show_side_nav' => $sideNav]);
        Page::create(['title' => 'Podstrona B', 'slug' => 'podstrona-b', 'type' => 'standard', 'is_published' => true, 'parent_id' => $parent->id, 'order' => 2, 'show_side_nav' => $sideNav]);
        Page::create(['title' => 'Wnuk A1', 'slug' => 'wnuk-a1', 'type' => 'standard', 'is_published' => true, 'parent_id' => $a->id, 'show_side_nav' => $sideNav]);

        return $parent;
    }

    /** HTML pierwszego elementu pasującego do zapytania XPath. */
    private function fragment(string $html, string $xpath): string
    {
        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
        libxml_clear_errors();
        $node = (new \DOMXPath($doc))->query($xpath)->item(0);
        $this->assertNotNull($node, "Brak elementu {$xpath}");

        return $doc->saveHTML($node);
    }

    private function assertFlatStyle(string $html, string $label): void
    {
        preg_match_all('/\s:?class="([^"]*)"/', $html, $m);
        $classes = implode(' ', $m[1]);

        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])text-brand(?![-\w])/', $classes, "{$label}: text-brand");
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])rounded-full(?![-\w])/', $classes, "{$label}: rounded-full");
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])border(?:-[\w\/.\[\]#-]+)?(?![-\w])/', $classes, "{$label}: border");
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])divide-/', $classes, "{$label}: divide");
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])opacity-\d/', $classes, "{$label}: opacity");
        foreach (preg_split('/\s+/', $classes) as $token) {
            if (preg_match('/(^|:)ring(-|$)/', $token)) {
                $this->assertMatchesRegularExpression('/^focus(-visible|-within)?:/', $token, "{$label}: {$token}");
            }
        }
        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(?:[\w-]+:)?(?:text|bg|border|ring|from|to|via|fill|stroke)-(?:green|amber|blue|red|purple|yellow|orange|emerald|indigo|sky|teal|rose|pink|violet|lime|cyan|fuchsia)-\d/', $classes, "{$label}: kolor spoza palety");
        $this->assertDoesNotMatchRegularExpression('/border\s*:\s*\d/i', $html, "{$label}: CSS border");
        $this->assertDoesNotMatchRegularExpression('/border-(?:top|bottom|left|right)\s*:/i', $html, "{$label}: CSS border-*");
    }

    public function test_menu_dzialu_boczne_jest_plaskie_z_oznaczona_biezaca_strona(): void
    {
        $this->section();

        $html = $this->get('/podstrona-a')->assertOk()->getContent();
        $nav = $this->fragment($html, '//*[@aria-label="Podstrony w tym dziale"]');

        $this->assertFlatStyle($nav, 'menu boczne');
        $this->assertStringContainsString('fa-chevron-right', $nav);
        $this->assertMatchesRegularExpression('/aria-current="?page"?[^>]*class="[^"]*bg-brand-light font-bold/', $nav);
        $this->assertStringContainsString('Wnuk A1', $nav);
    }

    public function test_menu_dzialu_w_stylu_panelu_jest_plaskie(): void
    {
        $this->section('panel');

        $nav = $this->fragment($this->get('/podstrona-a')->assertOk()->getContent(), '//nav[@aria-label="Podstrony w tym dziale"]');

        $this->assertFlatStyle($nav, 'menu panel');
        $this->assertStringContainsString('rounded-md bg-gray-50', $nav);
        $this->assertStringContainsString('aria-current="page"', $nav);
        $this->assertStringContainsString('Wnuk A1', $nav);
    }

    public function test_drzewo_dzialu_jest_plaskie_i_zachowuje_rozwijanie(): void
    {
        $this->section('tree');

        $nav = $this->fragment($this->get('/wnuk-a1')->assertOk()->getContent(), '//nav[@aria-label="Podstrony w tym dziale"]');

        $this->assertFlatStyle($nav, 'drzewo');
        $this->assertStringContainsString('Jesteś tu:', $nav);
        $this->assertStringContainsString('aria-controls="tree-branch-', $nav);
        $this->assertStringContainsString('aria-expanded="true"', $nav);
    }

    public function test_zakladki_dzialu_sa_plaskie(): void
    {
        $this->section('tabs');

        $nav = $this->fragment($this->get('/podstrona-b')->assertOk()->getContent(), '//nav[@aria-label="Podstrony w tym dziale"]');

        $this->assertFlatStyle($nav, 'zakładki');
        $this->assertStringContainsString('aria-current="page"', $nav);
    }

    public function test_spis_tresci_jest_plaski_w_obu_wariantach(): void
    {
        $this->section(null, false);

        $html = $this->get('/dzial')->assertOk()->getContent();
        $mobile = $this->fragment($html, '//details[.//nav[@aria-label="Spis treści"]]');
        $desktop = $this->fragment($html, '//nav[@id="toc-desktop"]');

        $this->assertFlatStyle($mobile, 'spis treści (mobile)');
        $this->assertFlatStyle($desktop, 'spis treści (desktop)');
        $this->assertStringContainsString('href="#podsekcja"', $desktop);
        $this->assertStringContainsString(':aria-current=', $desktop);
    }

    public function test_blok_w_tym_dziale_i_poprzednia_nastepna_sa_plaskie(): void
    {
        $this->section(null, false);

        $cards = $this->fragment($this->get('/dzial')->assertOk()->getContent(), '//*[@aria-labelledby="section-children-heading"]');
        $this->assertFlatStyle($cards, 'W tym dziale');
        $this->assertStringContainsString('stretched-link', $cards);

        $pager = $this->fragment($this->get('/podstrona-b')->assertOk()->getContent(), '//nav[@aria-label="Nawigacja w dziale"]');
        $this->assertFlatStyle($pager, 'poprzednia/następna');
        $this->assertStringContainsString('rel="prev"', $pager);
    }

    public function test_galeria_jest_plaska(): void
    {
        $page = new Page(['title' => 'Galeria', 'slug' => 'galeria', 'show_gallery' => true]);
        $page->setRelation('images', collect([
            (object) ['image_url' => '/a.jpg', 'alt' => 'Zdjęcie A', 'caption' => 'Podpis A'],
            (object) ['image_url' => '/b.jpg', 'alt' => 'Zdjęcie B', 'caption' => null],
        ]));

        foreach (['default', 'feer'] as $template) {
            $html = view('partials.page-gallery', ['page' => $page, 'siteSettings' => (object) ['site_template' => $template]])->render();

            $this->assertStringContainsString('Zdjęcie A', $html);
            $this->assertStringContainsString('Podpis A', $html);
            $this->assertFlatStyle($html, "galeria ({$template})");
        }
    }

    public function test_pliki_do_pobrania_bez_plakietek_z_rodzajem_zwyklym_tekstem(): void
    {
        $html = view('partials.attachments-list', ['attachments' => collect([
            (object) ['label' => 'Regulamin', 'file_extension' => 'PDF', 'file_size' => '1,2 MB', 'file_url' => '/regulamin.pdf'],
            (object) ['label' => 'Formularz', 'file_extension' => 'docx', 'file_size' => null, 'file_url' => '/formularz.docx'],
        ])])->render();

        $this->assertFlatStyle($html, 'pliki');
        $this->assertStringContainsString('PDF · 1,2 MB', $html);
        $this->assertStringContainsString('DOCX', $html);
        $this->assertStringNotContainsString('dl-type', $html);
        $this->assertStringNotContainsString('#b91c1c', $html);
        $this->assertStringContainsString('class="dl-card"', $html);
        $this->assertStringContainsString('Pobierz<span class="sr-only"> plik: Regulamin (PDF · 1,2 MB)</span>', $html);
    }
}
