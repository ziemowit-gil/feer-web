<?php

namespace Tests\Feature;

use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogTag;

/** Podpowiedzi tagów (combobox), wyłączenie sprawdzania nagłówków WYSIWYG w trybie Markdown, zdjęcia autorów na kartach. */
class BlogEditorExtrasTest extends BlogTestCase
{
    public function test_podpowiedzi_tagow_filtrowane_po_poczatku(): void
    {
        $a = $this->article('Pierwszy');
        $a->tags()->sync(BlogTag::idsFromList('dostępność, WCAG, Dobre praktyki'));
        $this->article('Drugi')->tags()->sync(BlogTag::idsFromList('dostępność'));
        BlogTag::create(['name' => 'Edukacja', 'slug' => 'edukacja']);
        BlogTag::create(['name' => '100%_pewne', 'slug' => '100-pewne']);

        $admin = $this->admin();
        $json = $this->actingAs($admin)->getJson(route('admin.wiem-feer.tagi', ['q' => 'do']))->assertOk()->json('tags');
        // Prefiks (nie „zawiera") i najpierw najczęściej używane.
        $this->assertSame(['dostępność', 'Dobre praktyki'], array_column($json, 'name'));
        $this->assertSame(2, $json[0]['count']);

        $this->assertSame(['WCAG'], array_column($this->actingAs($admin)->getJson(route('admin.wiem-feer.tagi', ['q' => 'wc']))->json('tags'), 'name'));
        // Znaki specjalne LIKE traktowane dosłownie.
        $this->assertSame([], $this->actingAs($admin)->getJson(route('admin.wiem-feer.tagi', ['q' => '%']))->json('tags'));
        $this->assertCount(5, $this->actingAs($admin)->getJson(route('admin.wiem-feer.tagi'))->json('tags'));
    }

    public function test_podpowiedzi_tagow_wymagaja_logowania(): void
    {
        $status = $this->getJson(route('admin.wiem-feer.tagi', ['q' => 'a']))->status();
        $this->assertContains($status, [302, 401]);
    }

    public function test_pole_tagow_ma_combobox_i_dziala_bez_js(): void
    {
        $this->actingAs($this->admin())->get(route('admin.wiem-feer.create'))->assertOk()
            ->assertSee('data-tag-combobox data-url="' . route('admin.wiem-feer.tagi') . '"', false)
            ->assertSee('<input type="text" id="tags" name="tags"', false)
            ->assertSee('id="tags-listbox" role="listbox"', false)
            ->assertSee("input.setAttribute('role', 'combobox')", false);
    }

    public function test_sprawdzanie_naglowkow_wysiwyg_wylaczone_w_trybie_markdown(): void
    {
        $admin = $this->admin();

        // Nowy wpis (Markdown): formularz wyłącza wspólne sprawdzenie, ma własne dla Markdown.
        $this->actingAs($admin)->get(route('admin.wiem-feer.create'))->assertOk()
            ->assertSee('data-skip-heading-check', false)
            ->assertSee('function markdownHeadingProblems', false);

        // Wpis HTML: edytor wizualny, sprawdzenie aktywne (atrybut tylko przełączany przez JS).
        $html = $this->article('Wpis HTML', ['body_format' => 'html']);
        $content = $this->actingAs($admin)->get(route('admin.wiem-feer.edit', $html))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/<form[^>]*id="blog-article-form"[^>]*data-skip-heading-check/s', $content);
        $this->assertStringContainsString("if (form.hasAttribute('data-skip-heading-check')) return;", $content);
    }

    public function test_zdjecie_autora_na_karcie_i_we_wpisie_wyroznionym(): void
    {
        $withPhoto = BlogAuthor::create(['name' => 'Anna Zdjęciowa', 'slug' => 'anna', 'photo_path' => 'blog/anna.jpg', 'is_active' => true]);
        $noPhoto = BlogAuthor::create(['name' => 'Jan Bezzdjęciowy', 'slug' => 'jan', 'is_active' => true]);
        $this->article('Wyróżniony', ['is_featured' => true, 'author_id' => $withPhoto->id]);
        $this->article('Zwykły ze zdjęciem', ['author_id' => $withPhoto->id, 'published_at' => now()->subDays(2)]);
        $this->article('Zwykły bez zdjęcia', ['author_id' => $noPhoto->id, 'published_at' => now()->subDays(3)]);

        $html = $this->get('/wiem')->assertOk()->getContent();

        $img = '<img src="' . $withPhoto->photoUrl() . '" alt="" width="32" height="32" loading="lazy" class="h-8 w-8 shrink-0 rounded-md object-cover">';
        // Wpis wyróżniony + karta.
        $this->assertSame(2, substr_count($html, $img));
        $this->assertStringContainsString('Jan Bezzdjęciowy', $html);
        $this->assertStringNotContainsString('rounded-full', substr($html, strpos($html, 'Jan Bezzdjęciowy') - 300, 300));
    }
}
