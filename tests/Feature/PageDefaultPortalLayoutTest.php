<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Domyślny widok strony (page/show) w układzie artykułu portalu: dział nad tytułem,
 * pasek z datą aktualizacji i rozmiarem tekstu, kolumna boczna (spis treści, menu działu)
 * oraz „Zobacz także w dziale", gdy menu działu jest wyłączone.
 */
class PageDefaultPortalLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function page(array $attributes): Page
    {
        return Page::create($attributes + ['type' => 'standard', 'is_published' => true, 'content' => '<p>Treść strony</p>']);
    }

    /** @return array{0: Page, 1: Page} [dział, bieżąca podstrona] */
    private function section(bool $sideNav): array
    {
        $parent = $this->page(['title' => 'Dział usług', 'slug' => 'dzial-uslug', 'show_side_nav' => $sideNav]);
        $current = $this->page(['title' => 'Bieżąca podstrona', 'slug' => 'biezaca', 'parent_id' => $parent->id, 'show_side_nav' => $sideNav]);
        $this->page(['title' => 'Siostrzana strona', 'slug' => 'siostrzana', 'parent_id' => $parent->id, 'content' => '<p>Fragment siostrzanej treści</p>']);
        $this->page(['title' => 'Szkic działu', 'slug' => 'szkic-dzialu', 'parent_id' => $parent->id, 'is_published' => false]);

        return [$parent, $current];
    }

    private function aside(string $html): string
    {
        $start = strpos($html, '<aside aria-label="Kolumna boczna"');
        $this->assertNotFalse($start, 'Brak kolumny bocznej.');

        return substr($html, $start, strrpos($html, '</aside>') - $start);
    }

    public function test_standard_page_has_portal_header_with_meta_bar_and_text_size(): void
    {
        $this->page(['title' => 'Zwykła strona', 'slug' => 'zwykla']);

        $html = $this->get('/zwykla')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'), 'Dokładnie jeden nagłówek h1.');
        $this->assertStringContainsString('page-portal-article', $html);
        $this->assertStringNotContainsString('Zaktualizowano', $html);
        $this->assertStringNotContainsString('min czytania', $html);
        $this->assertStringContainsString('aria-label="Rozmiar tekstu strony"', $html);
        $this->assertStringContainsString('feer-page-text-size', $html);
        $this->assertStringContainsString('Największy tekst', $html);
        $this->assertStringContainsString('page-portal-body prose', $html);
        // Bez spisu treści i menu działu — bez kolumny bocznej i „Zobacz także".
        $this->assertStringNotContainsString('aria-label="Kolumna boczna"', $html);
        $this->assertStringNotContainsString('Zobacz także w dziale', $html);
    }

    public function test_sidebar_contains_toc_when_content_has_headings(): void
    {
        $this->page(['title' => 'Poradnik', 'slug' => 'poradnik', 'content' => '<h2>Pierwszy krok</h2><p>A</p><h2>Drugi krok</h2><p>B</p><h2>Trzeci krok</h2><p>C</p>']);

        $html = $this->get('/poradnik')->assertOk()->getContent();

        $aside = $this->aside($html);
        $this->assertStringContainsString('lg:col-span-3', $html);
        $this->assertStringContainsString('md:sticky md:top-24', $html);
        $this->assertStringContainsString('id="toc-desktop"', $aside);
        $this->assertStringContainsString('Drugi krok', $aside);
        // Wariant mobilny spisu nad treścią zostaje.
        $this->assertStringContainsString('id="toc-mobile"', $html);
    }

    public function test_section_menu_in_sidebar_and_section_label_above_title(): void
    {
        [, $current] = $this->section(true);

        $html = $this->get($current->publicUrl())->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~uppercase tracking-widest">\s*<a href="[^"]*dzial-uslug"[^>]*>Dział usług</a>~', $html);
        $aside = $this->aside($html);
        $this->assertStringContainsString('Podstrony w tym dziale', $aside);
        $this->assertStringContainsString('Siostrzana strona', $aside);
        $this->assertStringNotContainsString('Zobacz także w dziale', $html);
    }

    public function test_see_also_grid_when_side_nav_disabled(): void
    {
        [, $current] = $this->section(false);

        $html = $this->get($current->publicUrl())->assertOk()->getContent();

        $this->assertStringNotContainsString('Podstrony w tym dziale', $html);
        $start = strpos($html, 'Zobacz także w dziale');
        $this->assertNotFalse($start);
        $seeAlso = substr($html, $start, strpos($html, '</section>', $start) - $start);
        $this->assertStringContainsString('Siostrzana strona', $seeAlso);
        $this->assertStringContainsString('Fragment siostrzanej treści', $seeAlso);
        $this->assertStringNotContainsString('Bieżąca podstrona', $seeAlso);
        $this->assertStringNotContainsString('Szkic działu', $seeAlso);
    }

    public function test_typed_page_keeps_its_own_view(): void
    {
        $this->page([
            'title' => 'Audyt dostępności', 'slug' => 'audyt', 'type' => 'service',
            'type_data' => ['lead' => 'Sprawdzimy Twoją stronę.', 'cta_label' => 'Zapytaj o wycenę', 'cta_url' => '/kontakt'],
        ]);

        $html = $this->get('/audyt')->assertOk()->getContent();

        $this->assertStringContainsString('Zapytaj o wycenę', $html);
        $this->assertStringNotContainsString('page-portal-article', $html);
        $this->assertStringNotContainsString('Rozmiar tekstu strony', $html);
    }

    public function test_admin_gets_inline_edit_markup(): void
    {
        $this->page(['title' => 'Edytowalna', 'slug' => 'edytowalna']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get('/edytowalna')->assertOk()->getContent();

        $this->assertStringContainsString('inlineContentEditor(\'page\'', $html);
        $this->assertStringContainsString('data-inline-field="title"', $html);
        $this->assertStringContainsString('data-inline-field="content"', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }

    public function test_tree_nav_stays_in_left_column(): void
    {
        $root = $this->page(['title' => 'Dział drzewa', 'slug' => 'dzial-drzewa', 'side_nav_style' => 'tree']);
        $this->page(['title' => 'Gałąź', 'slug' => 'galaz', 'parent_id' => $root->id]);

        $html = $this->get('/dzial-drzewa')->assertOk()->getContent();

        $this->assertStringContainsString('md:grid-cols-[260px_1fr]', $html);
        $this->assertStringContainsString('md:order-first', $html);
    }
}
