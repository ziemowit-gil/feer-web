<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Sekcja „Dokumenty i sprawozdania” na stronie „O nas”: wyłączanie BIP i przycisk dodatkowych dokumentów. */
class AboutDocumentsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bip_box_shown_by_default(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_documents_intro' => 'Publikujemy dokumenty.']);

        $html = $this->get($page->publicUrl())->assertOk()->getContent();
        $this->assertStringContainsString('Biuletynie Informacji Publicznej', $this->section($html));
    }

    public function test_bip_hidden_with_more_documents_button(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
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
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $page = Page::create(['title' => 'O nas', 'slug' => 'o-nas-test', 'type' => 'about', 'is_published' => true,
            'about_documents_intro' => 'Publikujemy dokumenty.']);

        $section = $this->section($this->get($page->publicUrl())->assertOk()->getContent());
        $this->assertStringContainsString('href="'.route('reports.index').'"', $section);
        $this->assertMatchesRegularExpression('#<a href="[^"]+"[^>]*>sprawozdania</a>#', $section);
    }

    private function section(string $html): string
    {
        $start = strpos($html, 'id="sekcja-documents"');
        $this->assertNotFalse($start, 'Sekcja dokumentów jest na stronie.');

        return substr($html, $start, strpos($html, '</section>', $start) - $start);
    }
}
