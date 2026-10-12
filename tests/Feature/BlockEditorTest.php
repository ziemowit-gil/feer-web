<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function page(array $attributes = []): Page
    {
        return Page::create($attributes + ['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Stara treść</p>', 'is_published' => true]);
    }

    public function test_renders_blocks_to_front_end_html(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'heading', 'attrs' => ['text' => 'Sekcja', 'level' => 2]],
            ['type' => 'paragraph', 'attrs' => ['text' => 'Tekst <strong>ważny</strong>', 'align' => 'center']],
            ['type' => 'list', 'attrs' => ['ordered' => true, 'items' => ['Jeden', 'Dwa', '']]],
            ['type' => 'buttons', 'attrs' => ['items' => [['label' => 'Zapisz się', 'url' => '/zapisy', 'variant' => 'red', 'newTab' => true]]]],
            ['type' => 'callout', 'attrs' => ['variant' => 'important'], 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'Uwaga']]]],
            ['type' => 'columns', 'attrs' => [], 'inner' => [
                ['type' => 'column', 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'A']]]],
                ['type' => 'column', 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'B']]]],
            ]],
            ['type' => 'table', 'attrs' => ['caption' => 'Ceny', 'headerRow' => true, 'rows' => [['Usługa', 'Cena'], ['Kurs', '100 zł']]]],
            ['type' => 'embed', 'attrs' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'title' => 'Film o nas']],
            ['type' => 'shortcode', 'attrs' => ['code' => '[formularz:zapisy]']],
        ]);

        $this->assertStringContainsString('<h2>Sekcja</h2>', $html);
        $this->assertStringContainsString('<p class="has-text-align-center">Tekst <strong>ważny</strong></p>', $html);
        $this->assertStringContainsString('<ol><li>Jeden</li><li>Dwa</li></ol>', $html);
        $this->assertStringContainsString('class="cta-button cta-red" target="_blank" rel="noopener"', $html);
        $this->assertStringContainsString('<div class="content-important"><p>Uwaga</p></div>', $html);
        $this->assertStringContainsString('<div class="content-columns content-columns--2"><div class="content-column"><p>A</p></div><div class="content-column"><p>B</p></div></div>', $html);
        $this->assertStringContainsString('<caption>Ceny</caption><thead><tr><th scope="col">Usługa</th>', $html);
        $this->assertStringContainsString('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Film o nas"', $html);
        $this->assertStringContainsString('<p>[formularz:zapisy]</p>', $html);
    }

    public function test_strips_unsafe_markup_and_unknown_blocks(): void
    {
        $html = BlockRenderer::render([
            ['type' => 'paragraph', 'attrs' => ['text' => 'a<script>alert(1)</script><a href="javascript:alert(1)" onclick="x">b</a><img src=x onerror=alert(1)><a href="/ok" style="color:red">c</a>']],
            ['type' => 'evil', 'attrs' => ['html' => '<script></script>']],
            ['type' => 'column', 'inner' => [['type' => 'paragraph', 'attrs' => ['text' => 'samotna kolumna']]]],
            ['type' => 'image', 'attrs' => ['url' => 'javascript:alert(1)', 'alt' => 'x']],
            ['type' => 'buttons', 'attrs' => ['items' => [['label' => '"><script>', 'url' => 'data:text/html,x']]]],
        ]);

        $this->assertStringNotContainsString('script>alert', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('samotna kolumna', $html);
        $this->assertStringContainsString('<p>ab<a href="/ok">c</a></p>', $html);
        $this->assertStringContainsString('href="#" class="cta-button">&quot;&gt;&lt;script&gt;</a>', $html);
    }

    public function test_admin_saves_blocks_and_content_from_the_editor(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertSee('block-editor', false);

        $this->actingAs($this->admin())
            ->putJson(route('admin.podstrony.bloki.update', $page), [
                'title' => 'Nowy tytuł',
                'meta_description' => 'Opis',
                'blocks' => [
                    ['id' => 'b1', 'type' => 'heading', 'attrs' => ['text' => 'Witaj', 'level' => 2]],
                    ['id' => 'b2', 'type' => 'paragraph', 'attrs' => ['text' => 'Treść']],
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $page->refresh();
        $this->assertSame('Nowy tytuł', $page->title);
        $this->assertSame("<h2>Witaj</h2>\n<p>Treść</p>", $page->content);
        $this->assertSame('heading', $page->blocks[0]['type']);
        $this->assertArrayNotHasKey('id', $page->blocks[0]);

        $this->get('/strona')->assertOk()->assertSee('Witaj');
    }

    public function test_content_changed_elsewhere_drops_stale_blocks(): void
    {
        $page = $this->page(['blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'x']]], 'content' => '<p>x</p>']);

        $page->update(['title' => 'Inny tytuł']);
        $this->assertNotNull($page->fresh()->blocks);

        $page->update(['content' => '<p>Zmienione w klasycznym edytorze</p>']);
        $this->assertNull($page->fresh()->blocks);
    }

    public function test_classic_content_opens_as_html_block(): void
    {
        $page = $this->page(['content' => '<p>Stara treść</p>']);

        $this->actingAs($this->admin())
            ->get(route('admin.podstrony.bloki.edit', $page))
            ->assertOk()
            ->assertSee('"type":"html"', false)
            ->assertSee('"imported":true', false);
    }

    public function test_locked_page_and_guests_are_rejected(): void
    {
        $page = $this->page(['is_locked' => true]);
        $editor = User::factory()->create(['role' => User::ROLE_EDITOR]);

        $this->putJson(route('admin.podstrony.bloki.update', $page), ['title' => 'x', 'blocks' => []])->assertRedirect();

        if ($editor->canAccessModule('pages')) {
            $this->actingAs($editor)
                ->putJson(route('admin.podstrony.bloki.update', $page), ['title' => 'x', 'blocks' => []])
                ->assertForbidden();
        }

        $this->actingAs($this->admin())
            ->putJson(route('admin.podstrony.bloki.update', $page), ['title' => 'x', 'blocks' => []])
            ->assertOk();
    }

    private function news(array $attributes = []): News
    {
        return News::create($attributes + ['title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>Stara treść</p>', 'is_published' => true, 'published_at' => now()->subDay()]);
    }

    private function project(array $attributes = []): Project
    {
        $category = \App\Models\Category::firstOrCreate(['slug' => 'kategoria'], ['name' => 'Kategoria']);

        return Project::create($attributes + ['title' => 'Działanie', 'slug' => 'dzialanie', 'category_id' => $category->id, 'content' => '<p>Stary opis</p>', 'is_published' => true]);
    }

    public function test_link_search_returns_published_pages_news_and_projects(): void
    {
        $this->page(['title' => 'Rehabilitacja ruchowa', 'slug' => 'rehabilitacja']);
        $this->page(['title' => 'Rehabilitacja szkic', 'slug' => 'rehabilitacja-szkic', 'is_published' => false]);
        $this->news(['title' => 'Rehabilitacja w Lublinie', 'slug' => 'rehabilitacja-lublin']);
        $this->news(['title' => 'Rehabilitacja jutro', 'slug' => 'rehabilitacja-jutro', 'published_at' => now()->addWeek()]);
        $this->project(['title' => 'Rehabilitacja dzieci', 'slug' => 'rehabilitacja-dzieci']);
        $this->project(['title' => 'Rehabilitacja ukryta', 'slug' => 'rehabilitacja-ukryta', 'is_published' => false]);
        $this->page(['title' => 'Kontakt', 'slug' => 'kontakt']);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.edytor-blokowy.links', ['q' => 'rehabilitacja']))
            ->assertOk();

        $items = collect($response->json());
        $this->assertEqualsCanonicalizing(['Rehabilitacja ruchowa', 'Rehabilitacja w Lublinie', 'Rehabilitacja dzieci'], $items->pluck('title')->all());
        $this->assertEqualsCanonicalizing(['Strona', 'Aktualność', 'Działanie'], $items->pluck('kind')->all());
        $this->assertSame('/rehabilitacja', $items->firstWhere('kind', 'Strona')['url']);
        $this->assertSame('/aktualnosci/rehabilitacja-lublin', $items->firstWhere('kind', 'Aktualność')['url']);
        $this->assertSame('/dzialania/rehabilitacja-dzieci', $items->firstWhere('kind', 'Działanie')['url']);

        $this->getJson(route('admin.edytor-blokowy.links'))->assertOk()->assertJsonFragment(['title' => 'Kontakt']);
    }

    public function test_shortcode_preview_validates_and_renders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => 'to nie jest shortcode'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->actingAs($admin)
            ->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => '[nieznany:kod]'])
            ->assertOk()
            ->assertJson(['html' => '<p>[nieznany:kod]</p>']);

        $html = $this->actingAs($admin)
            ->postJson(route('admin.edytor-blokowy.shortcode'), ['html' => '<p>Tekst</p><script>alert(1)</script>[pasek:999999]'])
            ->assertOk()
            ->json('html');
        $this->assertStringContainsString('<p>Tekst</p>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('[pasek:999999]', $html);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_BIP_EDITOR]))
            ->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => '[blok:1]'])
            ->assertForbidden();
    }

    public function test_admin_saves_news_and_project_blocks(): void
    {
        $news = $this->news();
        $project = $this->project();
        $admin = $this->admin();
        $blocks = [
            ['id' => 'b1', 'type' => 'heading', 'attrs' => ['text' => 'Witaj', 'level' => 2]],
            ['id' => 'b2', 'type' => 'paragraph', 'attrs' => ['text' => 'Treść']],
        ];

        $this->actingAs($admin)
            ->get(route('admin.edytor-blokowy.edit', ['news', $news->id]))
            ->assertOk()
            ->assertSee('"type":"news"', false)
            ->assertSee('"imported":true', false)
            ->assertSee('Przejdź do klasycznego formularza aktualności', false);

        $this->actingAs($admin)
            ->get(route('admin.edytor-blokowy.edit', ['project', $project->id]))
            ->assertOk()
            ->assertSee('Przejdź do klasycznego formularza działania', false)
            // Płótno w układzie strony działania: kategoria, „Opis działania”, menu boczne od „O projekcie”.
            ->assertSee('"kind":"project"', false)
            ->assertSee('"heading":"Opis dzia', false)
            ->assertSee('"nav":["O projekcie"', false);

        $this->actingAs($admin)
            ->get(route('admin.edytor-blokowy.edit', ['news', $news->id]))
            ->assertSee('"layout":null', false);

        foreach (['news' => $news, 'project' => $project] as $type => $record) {
            $this->actingAs($admin)
                ->putJson(route('admin.edytor-blokowy.update', [$type, $record->id]), [
                    'title' => 'Nowy tytuł '.$type,
                    'meta_description' => 'Opis',
                    'blocks' => $blocks,
                ])
                ->assertOk()
                ->assertJson(['ok' => true]);

            $record->refresh();
            $this->assertSame('Nowy tytuł '.$type, $record->title);
            $this->assertSame("<h2>Witaj</h2>\n<p>Treść</p>", $record->content);
            $this->assertSame('paragraph', $record->blocks[1]['type']);
        }

        $this->get('/aktualnosci/aktualnosc')->assertOk()->assertSee('Witaj');

        // Pasek edycji na stronie działania prowadzi do edytora blokowego.
        $this->actingAs($admin)->get('/dzialania/dzialanie')->assertOk()
            ->assertSee(route('admin.edytor-blokowy.edit', ['project', $project->id]), false);

        // Błąd walidacji wraca jako JSON (edytor pokazuje komunikat), nie jako przekierowanie.
        $this->actingAs($admin)
            ->putJson(route('admin.edytor-blokowy.update', ['news', $news->id]), ['title' => '', 'blocks' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_news_and_project_content_changed_elsewhere_drops_stale_blocks(): void
    {
        $blocks = [['type' => 'paragraph', 'attrs' => ['text' => 'x']]];
        $news = $this->news(['blocks' => $blocks, 'content' => '<p>x</p>']);
        $project = $this->project(['blocks' => $blocks, 'content' => '<p>x</p>']);

        foreach ([$news, $project] as $record) {
            $record->update(['title' => 'Inny tytuł']);
            $this->assertNotNull($record->fresh()->blocks);

            $record->update(['content' => '<p>Zmienione w klasycznym edytorze</p>']);
            $this->assertNull($record->fresh()->blocks);
        }
    }

    public function test_user_without_module_access_is_forbidden(): void
    {
        $news = $this->news();
        $project = $this->project();
        $bip = User::factory()->create(['role' => User::ROLE_BIP_EDITOR]);

        $this->actingAs($bip)->get(route('admin.edytor-blokowy.edit', ['news', $news->id]))->assertForbidden();
        $this->actingAs($bip)->putJson(route('admin.edytor-blokowy.update', ['project', $project->id]), ['title' => 'x', 'blocks' => []])->assertForbidden();
        $this->actingAs($bip)->getJson(route('admin.edytor-blokowy.links'))->assertForbidden();

        // Redaktor PR ma aktualności i działania, ale nie strony.
        $pr = User::factory()->create(['role' => User::ROLE_PR_EDITOR]);
        $this->actingAs($pr)->get(route('admin.edytor-blokowy.edit', ['news', $news->id]))->assertOk();
        $this->actingAs($pr)->get(route('admin.edytor-blokowy.edit', ['page', $this->page()->id]))->assertForbidden();
        $this->assertSame('Aktualność', $news->fresh()->title);
    }
}
