<?php

namespace Tests\Feature;

use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Support\HtmlToMarkdown;
use Modules\Blog\Support\MarkdownRenderer;

/** Edycja wpisu Wiem FEER: zakładki, podsumowanie błędów, edytor Markdown i konwersja HTML → Markdown. */
class BlogEditorTest extends BlogTestCase
{
    private const MARKDOWN = <<<'MD'
        ## Sekcja pierwsza

        Tekst z **pogrubieniem**, _kursywą_ i [linkiem](https://feer.org.pl).

        - punkt A
        - punkt B

        1. pierwszy
        2. drugi

        | Kolumna | Wartość |
        | --- | --- |
        | A | ~~1~~ |
        MD;

    public function test_nowy_wpis_w_markdown_renderuje_html(): void
    {
        $this->actingAs($this->admin())->post(route('admin.wiem-feer.store'), [
            'title' => 'Wpis w Markdown', 'body_format' => 'markdown', 'body_markdown' => self::MARKDOWN, 'is_published' => '1',
        ])->assertRedirect(route('admin.wiem-feer.index'));

        $article = BlogArticle::firstOrFail();
        $this->assertTrue($article->isMarkdown());
        $this->assertSame(self::MARKDOWN, $article->body_markdown);
        $this->assertStringContainsString('<h2>Sekcja pierwsza</h2>', $article->body);
        $this->assertStringContainsString('<strong>pogrubieniem</strong>', $article->body);
        $this->assertStringContainsString('<a href="https://feer.org.pl">linkiem</a>', $article->body);
        $this->assertStringContainsString('<li>punkt A</li>', $article->body);
        $this->assertStringContainsString('<ol>', $article->body);
        $this->assertStringContainsString('<th scope="col">Kolumna</th>', $article->body);
        $this->assertStringContainsString('<del>1</del>', $article->body);

        // Strona publiczna nadal czyta `body`.
        $this->get('/wiem/wpis-w-markdown')->assertOk()->assertSee('<strong>pogrubieniem</strong>', false);
    }

    public function test_zapisz_zostaje_w_edycji(): void
    {
        $this->actingAs($this->admin())->post(route('admin.wiem-feer.store'), [
            'title' => 'Zostań', 'body_format' => 'markdown', 'body_markdown' => 'Tekst', 'after_save' => 'edit',
        ])->assertRedirect(route('admin.wiem-feer.edit', 'zostan'));
    }

    public function test_markdown_usuwa_skrypty_atrybuty_on_i_javascript(): void
    {
        $html = MarkdownRenderer::toHtml(implode("\n\n", [
            '[klik](javascript:alert(1))',
            '<a href="jav&#x09;ascript:alert(2)" onclick="alert(3)">a</a> <img src="x.png" alt="x" onerror="alert(4)">',
            '<script>alert(5)</script>',
            '<iframe src="https://www.youtube.com/embed/abc" title="Film"></iframe>',
        ]));

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
        $this->assertDoesNotMatchRegularExpression('/\son\w+=/i', $html);
        $this->assertStringContainsString('<img src="x.png" alt="x">', $html);
        $this->assertStringContainsString('<iframe src="https://www.youtube.com/embed/abc"', $html);

        $this->actingAs($this->admin())->post(route('admin.wiem-feer.store'), [
            'title' => 'Zły', 'body_format' => 'markdown', 'body_markdown' => "<script>alert(1)</script>\n\n<p onmouseover=\"x()\">Tekst</p>",
        ])->assertRedirect();
        $body = BlogArticle::firstOrFail()->body;
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onmouseover', $body);
        $this->assertStringContainsString('<p>Tekst</p>', $body);
    }

    public function test_pusty_markdown_daje_blad(): void
    {
        $this->actingAs($this->admin())->post(route('admin.wiem-feer.store'), [
            'title' => 'Pusty', 'body_format' => 'markdown', 'body_markdown' => '   ',
        ])->assertSessionHasErrors('body_markdown');
        $this->assertSame(0, BlogArticle::count());
    }

    public function test_podglad_markdown_zwraca_html_i_wymaga_logowania(): void
    {
        $this->postJson(route('admin.wiem-feer.podglad-markdown'), ['markdown' => '## A'])->assertStatus(302);

        $this->actingAs($this->admin())->postJson(route('admin.wiem-feer.podglad-markdown'), ['markdown' => "## Nagłówek\n\nRaz dwa trzy <script>x()</script>"])
            ->assertOk()
            ->assertJsonPath('words', 4)
            ->assertJsonPath('minutes', 1)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, '<h2>Nagłówek</h2>') && ! str_contains($html, '<script'))->etc());
    }

    public function test_wpis_html_zostaje_html(): void
    {
        $article = $this->article('Stary wpis', ['body' => '<p>Stara <strong>treść</strong></p>']);
        $this->assertSame('html', $article->fresh()->body_format);

        $this->actingAs($this->admin())->get(route('admin.wiem-feer.edit', $article))->assertOk()
            ->assertSee('id="editor-body"', false)
            ->assertSee('Przełącz na Markdown')
            ->assertSee('name="body_format" id="body_format" value="html"', false);

        // Zapis bez body_format (np. stary formularz) — zostaje HTML, treść z edytora wizualnego.
        $this->actingAs($this->admin())->put(route('admin.wiem-feer.update', $article), [
            'title' => 'Stary wpis', 'body' => '<p>Nowa treść</p>', 'body_markdown' => '', 'is_published' => '1',
        ])->assertRedirect();
        $fresh = $article->fresh();
        $this->assertSame('html', $fresh->body_format);
        $this->assertSame('<p>Nowa treść</p>', $fresh->body);

        // Przełączenie na Markdown w formularzu: body renderowane z Markdown.
        $this->actingAs($this->admin())->put(route('admin.wiem-feer.update', $article), [
            'title' => 'Stary wpis', 'body' => '<p>Nowa treść</p>', 'body_format' => 'markdown', 'body_markdown' => 'Treść **md**',
        ])->assertRedirect();
        $this->assertSame('<p>Treść <strong>md</strong></p>', $article->fresh()->body);
    }

    public function test_nowy_wpis_domyslnie_w_markdown(): void
    {
        $this->actingAs($this->admin())->get(route('admin.wiem-feer.create'))->assertOk()
            ->assertSee('name="body_format" id="body_format" value="markdown"', false)
            ->assertSee('name="body_markdown"', false)
            ->assertSee('role="toolbar" aria-label="Formatowanie Markdown"', false)
            ->assertSee('aria-keyshortcuts="Control+B"', false)
            ->assertSee('Ściągawka Markdown')
            ->assertDontSee('id="editor-body"', false);
    }

    public function test_konwersja_html_do_markdown_zachowuje_typowe_elementy(): void
    {
        $html = '<h2>Tytuł</h2><p>Akapit z <strong>pogrubieniem</strong>, <em>kursywą</em> i <a href="https://feer.org.pl">linkiem</a>.<br>Druga linia</p>'
            . '<ul><li>jeden</li><li>dwa<ul><li>zagnieżdżony</li></ul></li></ul><ol><li>A</li><li>B</li></ol>'
            . '<blockquote><p>Cytat</p></blockquote><p><img src="/storage/a.jpg" alt="Opis zdjęcia"></p>'
            . '<pre><code>kod();</code></pre><hr><table><thead><tr><th>X</th><th>Y</th></tr></thead><tbody><tr><td>1</td><td>2</td></tr></tbody></table>'
            . '<div class="content-note"><p>Notatka</p></div>';

        $md = HtmlToMarkdown::convert($html);
        $this->assertStringContainsString('## Tytuł', $md);
        $this->assertStringContainsString('**pogrubieniem**', $md);
        $this->assertStringContainsString('[linkiem](https://feer.org.pl)', $md);
        $this->assertStringContainsString("- dwa\n  - zagnieżdżony", $md);
        $this->assertStringContainsString('![Opis zdjęcia](/storage/a.jpg)', $md);
        $this->assertStringContainsString("| X | Y |\n| --- | --- |\n| 1 | 2 |", $md);
        $this->assertStringContainsString('<div class="content-note"><p>Notatka</p></div>', $md);

        $back = MarkdownRenderer::toHtml($md);
        foreach (['<h2>Tytuł</h2>', '<strong>pogrubieniem</strong>', '<em>kursywą</em>', '<a href="https://feer.org.pl">linkiem</a>', '<br>',
            '<li>jeden</li>', '<li>zagnieżdżony</li>', '<ol>', '<blockquote>', '<img src="/storage/a.jpg" alt="Opis zdjęcia">',
            '<code>kod();', '<hr>', '<th scope="col">X</th>', '<td>2</td>', '<div class="content-note"><p>Notatka</p></div>'] as $fragment) {
            $this->assertStringContainsString($fragment, $back);
        }

        $this->actingAs($this->admin())->postJson(route('admin.wiem-feer.html-na-markdown'), ['html' => '<p>Hej <b>tu</b></p>'])
            ->assertOk()->assertJsonPath('markdown', "Hej **tu**\n");
    }

    public function test_zakladki_maja_poprawne_aria(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.wiem-feer.create'))->assertOk()->getContent();

        $this->assertStringContainsString('role="tablist" aria-label="Sekcje artykułu"', $html);
        foreach (['tresc', 'autorzy', 'zdjecie', 'publikacja', 'seo'] as $tab) {
            $this->assertMatchesRegularExpression('/role="tab" id="tab-' . $tab . '" aria-controls="panel-' . $tab . '"/', $html);
            $this->assertMatchesRegularExpression('/role="tabpanel" id="panel-' . $tab . '" aria-labelledby="tab-' . $tab . '"/', $html);
        }
        $tablist = substr($html, strpos($html, 'role="tablist"'), strpos($html, 'id="panel-tresc"') - strpos($html, 'role="tablist"'));
        $this->assertSame(1, substr_count($tablist, 'aria-selected="true"'));
        $this->assertMatchesRegularExpression('/id="tab-tresc"[^>]*aria-selected="true" tabindex="0"/', $html);
        $this->assertMatchesRegularExpression('/id="tab-seo"[^>]*aria-selected="false" tabindex="-1"/', $html);
        $this->assertStringContainsString('Zapisz i zamknij', $html);
        $this->assertStringContainsString('Tak może wyglądać wynik w Google', $html);
    }

    public function test_po_bledzie_podsumowanie_z_linkami_do_pol(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->from(route('admin.wiem-feer.create'))->post(route('admin.wiem-feer.store'), [
            'title' => '', 'body_format' => 'markdown', 'body_markdown' => 'Tekst', 'meta_description' => str_repeat('x', 400),
        ])->assertRedirect(route('admin.wiem-feer.create'));

        $html = $this->actingAs($admin)->get(route('admin.wiem-feer.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="error-summary"', $html);
        $this->assertMatchesRegularExpression('/href="#title" data-error-link data-tab="tresc" data-target="title"/', $html);
        $this->assertMatchesRegularExpression('/href="#meta_description" data-error-link data-tab="seo" data-target="meta_description"/', $html);
        $this->assertStringContainsString('data-first-error-tab="tresc"', $html);
        $this->assertMatchesRegularExpression('/id="title"[^>]*aria-invalid=true/s', $html);
        $this->assertStringContainsString('(zawiera błędy)', $html);
        // Wpisany Markdown wraca do pola.
        $this->assertStringContainsString('>Tekst</textarea>', $html);
    }
}
