<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Układ wpisu „portal": artykuł + przyklejona kolumna boczna działu + „Zobacz także",
 * w szablonie domyślnym i FEER.
 */
class NewsPortalLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettings();
    }

    private function resetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function news(array $attributes): News
    {
        return News::create($attributes + ['content' => '<p>Treść wpisu</p>', 'excerpt' => 'Zajawka wpisu', 'is_published' => true, 'published_at' => now()->subDay()]);
    }

    /** @return array{0: News, 1: NewsCategory} */
    private function scenario(string $layout = 'portal'): array
    {
        $category = NewsCategory::create(['name' => 'Dostępność', 'slug' => 'dostepnosc']);
        $projectCategory = \App\Models\Category::firstOrCreate(['slug' => 'kategoria'], ['name' => 'Kategoria']);
        $project = Project::create(['title' => 'Asystent osobisty', 'slug' => 'asystent', 'category_id' => $projectCategory->id, 'content' => '<p>Opis</p>', 'is_published' => true]);

        $main = $this->news(['title' => 'Główny wpis', 'slug' => 'glowny', 'news_category_id' => $category->id, 'project_id' => $project->id, 'article_layout' => $layout]);
        $this->news(['title' => 'Sąsiad w dziale', 'slug' => 'sasiad', 'news_category_id' => $category->id, 'published_at' => now()->subDays(2)]);
        $this->news(['title' => 'Szkic w dziale', 'slug' => 'szkic', 'news_category_id' => $category->id, 'is_published' => false]);
        $this->news(['title' => 'Wpis spoza działu', 'slug' => 'spoza', 'published_at' => now()->subDays(3)]);

        return [$main, $category];
    }

    private function assertPortal(string $html): void
    {
        $this->assertStringContainsString('news-portal-article', $html);
        $this->assertStringContainsString('lg:sticky lg:top-24', $html);

        // Kolumna boczna: dział jako nagłówek, inne opublikowane wpisy działu, link do całej kategorii.
        $aside = substr($html, strpos($html, '<aside'), strpos($html, '</aside>') - strpos($html, '<aside'));
        $this->assertStringContainsString('<h2 class="mb-3 text-lg font-bold text-ink">Dostępność</h2>', $aside);
        $this->assertStringContainsString('Sąsiad w dziale', $aside);
        $this->assertStringNotContainsString('Szkic w dziale', $aside);
        $this->assertStringNotContainsString('Główny wpis', $aside);
        $this->assertStringNotContainsString('Wpis spoza działu', $aside);
        $this->assertStringContainsString('Wszystkie w kategorii Dostępność', $aside);
        $this->assertStringContainsString('?kategoria=dostepnosc', $aside);
        $this->assertStringContainsString('Działanie', $aside);
        $this->assertStringContainsString('Asystent osobisty', $aside);

        // Zobacz także pod obiema kolumnami, bez szkiców i bez bieżącego wpisu.
        $this->assertStringContainsString('Zobacz także', $html);
        $seeAlso = substr($html, strpos($html, 'id="see-also-heading"'));
        $this->assertStringContainsString('Wpis spoza działu', $seeAlso);
        $this->assertStringNotContainsString('Szkic w dziale', $seeAlso);
        $this->assertGreaterThan(strpos($html, '</aside>'), strpos($html, 'id="see-also-heading"'));

        // Rozmiar tekstu A A A.
        $this->assertStringContainsString('aria-label="Rozmiar tekstu artykułu"', $html);
        foreach (['Tekst normalny', 'Większy tekst', 'Największy tekst'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertSame(3, substr_count($html, ":aria-pressed=\"(size === '"));
        $this->assertStringContainsString('localStorage', $html);

        // Treść, jeden H1, narzędzia artykułu zachowane.
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('id="article-text"', $html);
        $this->assertStringContainsString('Treść wpisu', $html);
        $this->assertStringContainsString('aria-label="Opcje artykułu"', $html);
    }

    public function test_uklad_portal_w_szablonie_domyslnym(): void
    {
        [$main] = $this->scenario();

        $this->assertPortal($this->get(route('news.show', $main))->assertOk()->getContent());
    }

    public function test_uklad_portal_w_szablonie_feer(): void
    {
        SiteSetting::current()->update(['site_template' => 'feer']);
        $this->resetSettings();
        [$main] = $this->scenario();

        $html = $this->get(route('news.show', $main))->assertOk()->getContent();
        $this->assertPortal($html);
        $this->assertStringNotContainsString('Czytaj także', $html);
    }

    public function test_inne_uklady_bez_zmian(): void
    {
        [$main] = $this->scenario('default');
        $html = $this->get(route('news.show', $main))->assertOk()->getContent();
        $this->assertStringNotContainsString('news-portal-article', $html);
        $this->assertStringNotContainsString('Zobacz także', $html);

        SiteSetting::current()->update(['site_template' => 'feer']);
        $this->resetSettings();
        $html = $this->get(route('news.show', $main))->assertOk()->getContent();
        $this->assertStringNotContainsString('news-portal-article', $html);
        $this->assertStringContainsString('Czytaj także', $html);
    }

    public function test_admin_moze_zapisac_uklad_portal(): void
    {
        $this->useClassicNewsForm();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk()
            ->assertSee('name="article_layout" value="portal"', false);

        $this->actingAs($admin)->post(route('admin.newsy.store'), [
            'title' => 'Portalowy', 'published_at' => now()->format('Y-m-d\TH:i'), 'is_published' => '1', 'article_layout' => 'portal',
        ])->assertSessionHasNoErrors();

        $this->assertSame('portal', News::where('title', 'Portalowy')->firstOrFail()->article_layout);

        $this->actingAs($admin)->post(route('admin.newsy.store'), [
            'title' => 'Zły', 'published_at' => now()->format('Y-m-d\TH:i'), 'article_layout' => 'cokolwiek',
        ])->assertSessionHasErrors('article_layout');
    }
}
