<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Database\Seeders\AboutPageHideRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sekcja „Dokumenty i sprawozdania” na stronie „O nas”: wyłączanie BIP i przycisk dodatkowych dokumentów. */
class AboutDocumentsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bip_box_shown_by_default(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_documents_intro' => 'Publikujemy dokumenty.']);

        $html = $this->get($page->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('Biuletynie Informacji Publicznej', $this->section($html));
    }

    public function test_bip_hidden_with_more_documents_button(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_documents_intro' => 'Publikujemy dokumenty.',
            'layout_data' => ['documents_hide_bip' => true, 'documents_more_url' => '/archiwum', 'documents_more_label' => 'Archiwum sprawozdań']]);
        $section = $this->section($this->get($page->publicUrl())->assertOk()->getContent());
        $this->assertStringContainsString('Publikujemy dokumenty.', $section);
        $this->assertStringNotContainsString('Biuletynie Informacji Publicznej', $section);
        $this->assertStringContainsString('href="/archiwum"', $section);
        $this->assertStringContainsString('Archiwum sprawozdań', $section);
    }

    public function test_reports_word_links_to_reports_when_module_enabled(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_documents_intro' => 'Publikujemy dokumenty.']);

        $section = $this->section($this->get($page->publicUrl())->assertOk()->getContent());
        $this->assertStringContainsString('href="'.route('reports.index').'"', $section);
        $this->assertMatchesRegularExpression('#<a href="[^"]+"[^>]*>sprawozdania</a>#', $section);
    }

    public function test_dane_organizacji_mozna_wylaczyc_a_uklad_ma_naglowek_i_pliki_osobno(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $settings = SiteSetting::first() ?? SiteSetting::create([]);
        $settings->update(['krs_number' => '0000779281', 'nip_number' => '7343570539']);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $visible = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true, 'about_documents_intro' => 'Publikujemy dokumenty.']);
        $this->assertStringContainsString('0000779281', $this->section($this->get($visible->publicUrl())->assertOk()->getContent()));

        $visible->update(['layout_data' => ['about_registry' => false]]);
        $section = $this->section($this->get($visible->publicUrl())->assertOk()->getContent());
        $this->assertStringNotContainsString('0000779281', $section);
        $this->assertStringContainsString('lg:items-start', $section, 'nagłówek i ramka BIP w jednym rzędzie nad plikami');
    }

    public function test_seeder_wylacza_dane_organizacji(): void
    {
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'about', 'is_published' => true, 'layout_data' => ['news_count' => '4']]);

        $this->seed(AboutPageHideRegistrySeeder::class);
        $this->seed(AboutPageHideRegistrySeeder::class);

        $layout = $page->fresh()->layout_data;
        $this->assertFalse($layout['about_registry']);
        $this->assertSame('4', $layout['news_count']);
    }

    public function test_wartosci_maja_pelny_rzad_bez_pustego_miejsca(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_values' => [['title' => 'A', 'text' => 'a'], ['title' => 'B', 'text' => 'b'], ['title' => 'C', 'text' => 'c']]]);

        $html = $this->get($page->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('grid-template-columns:repeat(auto-fit,minmax(min(100%,16rem),1fr))', $html);
        $this->assertStringNotContainsString('class="grid gap-6 sm:grid-cols-2">', substr($html, strpos($html, 'id="sekcja-values"')));
    }

    private function section(string $html): string
    {
        $start = strpos($html, 'id="sekcja-documents"');
        $this->assertNotFalse($start, 'Sekcja dokumentów jest na stronie.');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }
}
