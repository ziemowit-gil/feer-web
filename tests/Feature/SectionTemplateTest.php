<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function news(string $title, ?NewsCategory $category = null, int $daysAgo = 1): News
    {
        return News::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'excerpt' => 'Zajawka: '.$title,
            'content' => '<p>Treść</p>',
            'is_published' => true,
            'published_at' => now()->subDays($daysAgo),
            'news_category_id' => $category?->id,
        ]);
    }

    public function test_section_template_shows_news_and_own_subpages_menu(): void
    {
        $edu = NewsCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        $this->news('Najnowsza z edukacji', $edu, 1);
        $this->news('Starsza z edukacji', $edu, 3);
        $this->news('Spoza kategorii', null, 0);

        $section = Page::create([
            'title' => 'Dział dostępności', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true,
            'page_template' => 'section',
            'layout_data' => ['news_category_id' => $edu->id, 'news_count' => 4, 'slider' => true],
            'content' => '<p>O dziale</p>',
        ]);
        Page::create(['title' => 'Audyty', 'slug' => 'dzial-audyty', 'type' => 'standard', 'is_published' => true, 'parent_id' => $section->id]);
        Page::create(['title' => 'Szkolenia', 'slug' => 'dzial-szkolenia', 'type' => 'standard', 'is_published' => true, 'parent_id' => $section->id]);

        $html = $this->get('/dzial')->assertOk()
            ->assertSee('Aktualności')
            ->assertSee('Najnowsza z edukacji')
            ->assertSee('Starsza z edukacji')
            ->assertDontSee('Spoza kategorii')
            ->assertSee('Pełna treść')
            ->assertSee('Wszystkie aktualności: Edukacja')
            ->assertSee('aria-label="Kolumna boczna działu"', false)
            ->assertSee('Audyty')
            ->assertSee('Szkolenia')
            ->assertSee('O dziale')
            ->getContent();

        // Wyróżniona (najnowsza) jest przed starszą.
        $this->assertLessThan(strpos($html, 'Starsza z edukacji'), strpos($html, 'Najnowsza z edukacji'));
    }

    public function test_news_can_be_hidden_and_non_standard_types_keep_their_layout(): void
    {
        $this->news('Jakaś aktualność');
        Page::create(['title' => 'Bez aktualności', 'slug' => 'bez', 'type' => 'standard', 'is_published' => true, 'page_template' => 'section', 'layout_data' => ['news_count' => 0]]);
        $this->get('/bez')->assertOk()->assertDontSee('Jakaś aktualność');

        // Typ z własnym układem (np. FAQ) ignoruje szablon działu.
        Page::create(['title' => 'FAQ działu', 'slug' => 'faq-dzialu', 'type' => 'faq', 'is_published' => true, 'page_template' => 'section', 'faq_items' => [['question' => 'Pytanie?', 'answer' => '<p>Tak.</p>']]]);
        $this->get('/faq-dzialu')->assertOk()->assertSee('Pytanie?')->assertDontSee('aria-label="Kolumna boczna działu"', false);
    }

    public function test_blog_source_and_newsletter_options(): void
    {
        $this->news('Aktualność, której nie powinno być');

        // Źródło „Wiem FEER" przy wyłączonym module bloga: brak sekcji wpisów, bez błędu.
        Page::create(['title' => 'Dział bloga', 'slug' => 'dzial-bloga', 'type' => 'standard', 'is_published' => true, 'page_template' => 'section',
            'layout_data' => ['source' => 'blog', 'news_count' => 4, 'newsletter' => false]]);
        $this->get('/dzial-bloga')->assertOk()
            ->assertDontSee('Aktualność, której nie powinno być')
            ->assertDontSee('Zapisz się na newsletter');
    }

    public function test_newsletter_panel_uses_brand_form_texts_and_page_overrides(): void
    {
        $form = \Modules\Newsletter\Models\NewsletterForm::defaultFor(SiteSetting::current()->id);
        if (! $form) {
            $this->markTestSkipped('Moduł Newsletter nie ma formularza w środowisku testowym.');
        }
        $form->update(['heading' => 'Newsletter firmowy', 'lead' => 'Opis z formularza.', 'button_label' => 'Zapisz się', 'is_active' => true]);

        Page::create(['title' => 'Dział A', 'slug' => 'dzial-a', 'type' => 'standard', 'is_published' => true, 'page_template' => 'section', 'layout_data' => ['news_count' => 0]]);
        Page::create(['title' => 'Dział B', 'slug' => 'dzial-b', 'type' => 'standard', 'is_published' => true, 'page_template' => 'section',
            'layout_data' => ['news_count' => 0, 'newsletter_title' => 'Wiadomości z działu', 'newsletter_text' => 'Własny opis.', 'newsletter_button' => 'Chcę dostawać']]);

        $this->get('/dzial-a')->assertOk()->assertSee('Newsletter firmowy')->assertSee('Opis z formularza.')->assertSee('Zapisz się na newsletter');
        $this->get('/dzial-b')->assertOk()->assertSee('Wiadomości z działu')->assertSee('Własny opis.')->assertSee('Chcę dostawać')->assertDontSee('Opis z formularza.');
    }

    public function test_admin_saves_section_settings(): void
    {
        $edu = NewsCategory::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        $page = Page::create(['title' => 'Dział', 'slug' => 'dzial-admin', 'type' => 'standard', 'is_published' => true]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('data-template-fields="section"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Dział', 'slug' => 'dzial-admin', 'type' => 'standard', 'parent_id' => '', 'is_published' => '1',
            'page_template' => 'section',
            'layout_data' => ['news_category_id' => $edu->id, 'news_count' => 7, 'slider' => '0', 'source' => 'blog', 'newsletter' => '0'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('section', $page->page_template);
        $this->assertSame(7, (int) $page->layout_data['news_count']);
        $this->assertSame($edu->id, (int) $page->layout_data['news_category_id']);
        $this->assertFalse((bool) $page->layout_data['slider']);
        $this->assertSame('blog', $page->layout_data['source']);
        $this->assertFalse((bool) $page->layout_data['newsletter']);
    }
}
