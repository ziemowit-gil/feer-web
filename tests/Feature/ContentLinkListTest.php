<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use App\Support\ShortcodeParser;
use App\Support\SubpageList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Listy linków w treści: [podstrony…] i [aktualnosci…] (oba edytory). */
class ContentLinkListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SubpageList::setContextPage(null);
    }

    private function page(string $title, ?Page $parent = null, array $attrs = []): Page
    {
        static $order = 0;

        return Page::create($attrs + [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'content' => '<p>Treść strony '.$title.'</p>',
            'is_published' => true,
            'parent_id' => $parent?->id,
            'order' => $order++,
        ]);
    }

    /** Dział z podstronami: opublikowane, szkic, zaplanowana, wyłączona, ukryta na mapie strony i wnuk. */
    private function section(): Page
    {
        $root = $this->page('Dział');
        $this->page('Zeta pierwsza', $root, ['meta_description' => 'Opis zety']);
        $alfa = $this->page('Alfa druga', $root);
        $this->page('Wnuk alfy', $alfa);
        $this->page('Szkic', $root, ['is_published' => false]);
        $this->page('Przyszła', $root, ['publish_at' => now()->addWeek()]);
        $this->page('Wyłączona', $root, ['is_disabled' => true]);
        $this->page('Ukryta', $root, ['hide_in_sitemap' => true]);

        return $root;
    }

    public function test_subpages_shortcode_lists_published_children_of_current_page(): void
    {
        $root = $this->section();
        $root->update(['content' => '<p>Wstęp</p><p>[podstrony]</p>']);

        $html = $this->get('/dzial')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Podstrony: Dział"', $html);
        $this->assertMatchesRegularExpression('~Zeta pierwsza</a>.*Alfa druga</a>~s', $html);
        $this->assertStringNotContainsString('[podstrony]', $html);
        foreach (['Szkic', 'Przyszła', 'Wyłączona', 'Ukryta', 'Wnuk alfy'] as $hidden) {
            $this->assertStringNotContainsString('>'.$hidden.'</a>', $html);
        }
    }

    public function test_list_updates_itself_when_a_subpage_is_published(): void
    {
        $root = $this->section();
        $code = '[podstrony:strona='.$root->id.']';

        $this->assertStringNotContainsString('Nowa podstrona', ShortcodeParser::render('<p>'.$code.'</p>'));
        $this->page('Nowa podstrona', $root);
        $this->assertStringContainsString('Nowa podstrona</a>', ShortcodeParser::render('<p>'.$code.'</p>'));
    }

    public function test_options_alphabetical_two_levels_and_tiles_with_excerpt(): void
    {
        $root = $this->section();

        $html = ShortcodeParser::render('<p>[podstrony:strona='.$root->id.';sortowanie=alfabet;poziomy=2;wyglad=kafle]</p>');

        $this->assertStringContainsString('content-links--kafle', $html);
        $this->assertMatchesRegularExpression('~Alfa druga</a>.*Wnuk alfy</a>.*Zeta pierwsza</a>~s', $html);
        $this->assertStringContainsString('Opis zety', $html);
        $this->assertStringContainsString('Treść strony Alfa druga', $html);
        $this->assertStringNotContainsString('<p>[podstrony', $html);
    }

    public function test_manual_selection_keeps_given_order_and_skips_unpublished(): void
    {
        $a = $this->page('Pierwsza');
        $b = $this->page('Druga');
        $draft = $this->page('Robocza', null, ['is_published' => false]);

        $html = ShortcodeParser::render('[podstrony:wybrane='.$b->id.','.$draft->id.','.$a->id.']');

        $this->assertMatchesRegularExpression('~Druga</a>.*Pierwsza</a>~s', $html);
        $this->assertStringNotContainsString('Robocza', $html);
        $this->assertStringContainsString('aria-label="Powiązane strony"', $html);
    }

    public function test_block_renders_dynamic_shortcode_not_frozen_html(): void
    {
        $root = $this->section();

        $content = BlockRenderer::render([
            ['type' => 'subpages', 'attrs' => ['source' => 'page', 'page' => $root->id, 'sort' => 'alfabet', 'depth' => 2, 'layout' => 'zajawki']],
            ['type' => 'subpages', 'attrs' => ['source' => 'current', 'manual' => true, 'picked' => [5, 'x', 7], 'layout' => 'nieznany']],
            ['type' => 'newslist', 'attrs' => ['category' => 3, 'limit' => 99, 'sort' => 'alfabet', 'layout' => 'kafle']],
        ]);

        $this->assertStringContainsString('<p>[podstrony:strona='.$root->id.';sortowanie=alfabet;poziomy=2;wyglad=zajawki]</p>', $content);
        $this->assertStringContainsString('<p>[podstrony:wybrane=5,7]</p>', $content);
        $this->assertStringContainsString('<p>[aktualnosci:kategoria=3;liczba=30;sortowanie=alfabet;wyglad=kafle]</p>', $content);
        $this->assertStringNotContainsString('<nav', $content);
    }

    public function test_block_editor_saves_subpages_block_and_page_shows_the_list(): void
    {
        $root = $this->section();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $root), [
            'title' => 'Dział',
            'blocks' => [['type' => 'subpages', 'attrs' => ['source' => 'current', 'layout' => 'lista']]],
        ])->assertOk();

        $this->assertSame('<p>[podstrony]</p>', $root->fresh()->content);
        $this->assertSame('subpages', $root->fresh()->blocks[0]['type']);
        $this->get('/dzial')->assertOk()->assertSee('Zeta pierwsza</a>', false)->assertDontSee('Szkic</a>', false);
    }

    public function test_block_editor_preview_uses_edited_page_as_current_page(): void
    {
        $root = $this->section();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->postJson(route('admin.edytor-blokowy.shortcode'), ['code' => '[podstrony]', 'page_id' => $root->id])
            ->assertOk()->assertJsonFragment(['html' => ShortcodeParser::render('<p>[podstrony:strona='.$root->id.']</p>')]);
    }

    public function test_wysiwyg_placeholder_paragraph_is_expanded(): void
    {
        $root = $this->section();
        $root->update(['content' => '<p class="content-links-shortcode" data-summary="Lista podstron: podstrony bieżącej strony · tylko dzieci">[podstrony:wyglad=zajawki]</p>']);

        $html = $this->get('/dzial')->assertOk()->getContent();

        $this->assertStringContainsString('content-links--zajawki', $html);
        $this->assertStringNotContainsString('content-links-shortcode', $html);
        $this->assertStringContainsString('Opis zety', $html);
    }

    public function test_news_shortcode_lists_published_news_by_category(): void
    {
        $cat = NewsCategory::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);
        $other = NewsCategory::create(['name' => 'Inne', 'slug' => 'inne']);
        $base = ['content' => '<p>Treść</p>', 'is_published' => true];
        News::create($base + ['title' => 'Starsza', 'slug' => 'starsza', 'published_at' => now()->subDays(5), 'news_category_id' => $cat->id, 'excerpt' => 'Zajawka starszej']);
        News::create($base + ['title' => 'Nowsza', 'slug' => 'nowsza', 'published_at' => now()->subDay(), 'news_category_id' => $cat->id]);
        News::create($base + ['title' => 'Z innej', 'slug' => 'z-innej', 'published_at' => now()->subDay(), 'news_category_id' => $other->id]);
        News::create(['title' => 'Szkic newsa', 'slug' => 'szkic-newsa', 'is_published' => false, 'published_at' => now()->subDay(), 'news_category_id' => $cat->id] + $base);
        News::create($base + ['title' => 'Zaplanowana', 'slug' => 'zaplanowana', 'published_at' => now()->addDay(), 'news_category_id' => $cat->id]);

        $html = ShortcodeParser::render('<p>[aktualnosci:kategoria='.$cat->id.';wyglad=zajawki]</p>');

        $this->assertStringContainsString('aria-label="Aktualności: Szkolenia"', $html);
        $this->assertMatchesRegularExpression('~Nowsza</a>.*Starsza</a>~s', $html);
        $this->assertStringContainsString('Zajawka starszej', $html);
        $this->assertStringContainsString('<time', $html);
        foreach (['Z innej', 'Szkic newsa', 'Zaplanowana'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html);
        }

        $limited = ShortcodeParser::render('[aktualnosci:liczba=1;sortowanie=alfabet]');
        $this->assertSame(1, substr_count($limited, 'content-links-link'));
    }

    public function test_wysiwyg_editor_offers_link_list_dialog(): void
    {
        $page = $this->page('Edytowana');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('id="ll-root"', false)
            ->assertSee('Lista podstron…')
            ->assertSee('Lista aktualności…');
    }

    public function test_block_editor_shows_page_tiles_preview(): void
    {
        $page = $this->page('Z kafelkami', null, [
            'tiles_enabled' => true,
            'tiles' => [['label' => 'Panel kursanta', 'url' => '/panel', 'icon' => 'fa-solid fa-user']],
        ]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()->getContent();
        preg_match("~data-config='([^']*)'~", $html, $m);
        $config = json_decode($m[1] ?? '', true);

        $this->assertNull($config['pageTiles']['before']);
        $this->assertStringContainsString('Panel kursanta', $config['pageTiles']['after']);
    }

    public function test_block_editor_offers_classic_editor_inserts(): void
    {
        $page = $this->page('Edytowana blokowo');
        \App\Models\TileSet::create(['name' => 'Kafle działu', 'tiles' => []]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertOk()->getContent();
        preg_match("~data-config='([^']*)'~", $html, $m);
        $classic = json_decode($m[1] ?? '', true)['classic'];

        // Kreatory jak w klasycznym edytorze.
        $this->assertStringContainsString('id="tb-root"', $html);
        $this->assertStringContainsString('id="bb-root"', $html);
        $this->assertSame('Kafle działu', $classic['tileSets'][0]['label']);
        $this->assertSame(['bip', 'accentLeft', 'accentRight'], array_column($classic['snippets'], 'key'));
        $this->assertStringContainsString('bip-link-box', $classic['snippets'][0]['html']);
    }

    public function test_admin_drawer_on_news_links_to_block_editor(): void
    {
        $news = News::create(['title' => 'Piknik NGO', 'slug' => 'piknik-ngo', 'content' => '<p>Treść</p>', 'is_published' => true, 'published_at' => now()->subDay()]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('news.show', $news))->assertOk()
            ->assertSee('Ta aktualność')
            ->assertSee('href="'.route('admin.edytor-blokowy.edit', ['news', $news->id]).'"', false)
            ->assertSee('Edytuj w panelu');
    }

    public function test_block_editor_link_search_includes_people_and_events_and_schedule_cta(): void
    {
        $this->page('Anna Kowalska', null, ['type' => 'about_person', 'slug' => 'zespol/osoba/anna-kowalska']);
        $this->page('Harmonogram szkoleń', null, ['type' => 'schedule']);
        \App\Models\Event::create(['title' => 'Annowe warsztaty', 'slug' => 'annowe-warsztaty', 'lead' => 'Opis', 'description' => 'Opis', 'is_published' => true, 'starts_at' => now()->addWeek()]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $results = collect($this->actingAs($admin)->getJson(route('admin.edytor-blokowy.links', ['q' => 'Ann']))->assertOk()->json());
        $this->assertSame('Osoba', $results->firstWhere('title', 'Anna Kowalska')['kind'] ?? null);
        $this->assertSame('/wydarzenia/annowe-warsztaty', $results->firstWhere('title', 'Annowe warsztaty')['url'] ?? null);

        $page = $this->page('Edytowana');
        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->getContent();
        preg_match("~data-config='([^']*)'~", $html, $m);
        $config = json_decode($m[1] ?? '', true);
        $this->assertSame([['title' => 'Harmonogram szkoleń', 'url' => '/harmonogram-szkolen']], $config['classic']['schedules']);
        $this->assertSame(\App\Support\Edition::allows('docx_import') ? route('admin.editor.docx.import') : null, $config['urls']['docx']);
    }

    public function test_template_saved_from_block_editor_has_blocks_and_html(): void
    {
        $page = $this->page('Z szablonem');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->getContent();
        preg_match("~data-config='([^']*)'~", $html, $m);
        $this->assertSame('page', json_decode($m[1] ?? '', true)['urls']['templates']['type']);

        $this->actingAs($admin)->postJson(route('admin.szablony.store'), [
            'type' => 'page', 'name' => 'Szkolenie',
            'data' => ['blocks' => [['type' => 'heading', 'attrs' => ['text' => 'Program', 'level' => 2]], ['type' => 'nieznany']], 'meta_description' => 'Opis'],
        ])->assertOk();

        $data = \App\Models\ContentTemplate::where('name', 'Szkolenie')->first()->data;
        $this->assertCount(1, $data['blocks']);
        $this->assertSame('<h2>Program</h2>', $data['content']);
    }

    public function test_block_editor_saves_sidebar_settings_like_the_form(): void
    {
        $root = $this->page('Rodzic');
        $page = $this->page('Dziecko', $root);
        $other = $this->page('Inna');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $save = fn (array $settings) => $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), [
            'title' => 'Dziecko', 'blocks' => [['type' => 'paragraph', 'attrs' => ['text' => 'x']]], 'settings' => $settings,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->getContent();
        preg_match("~data-config='([^']*)'~", $html, $m);
        $config = json_decode($m[1] ?? '', true)['settings'];
        $this->assertSame($root->id, $config['values']['parent_id']);
        $this->assertStringContainsString('/admin/podstrony/'.$page->id, $config['destroy']);

        $save(['is_published' => true, 'parent_id' => $other->id, 'order' => 7, 'is_archived' => true])->assertOk()
            ->assertJsonPath('settings.parent_id', $other->id);
        $page->refresh();
        $this->assertSame([$other->id, 7, true, true], [$page->parent_id, $page->order, $page->is_archived, $page->is_published]);

        // Wersja robocza zdejmuje publikację niezależnie od przełącznika.
        $save(['is_published' => true, 'draft' => true, 'parent_id' => $other->id])->assertOk();
        $this->assertFalse($page->fresh()->is_published);

        // Pętla w drzewie stron jest blokowana.
        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $other), [
            'title' => 'Inna', 'blocks' => [], 'settings' => ['is_published' => true, 'parent_id' => $page->id],
        ])->assertStatus(422);

        // Bez klucza settings ustawienia się nie zmieniają.
        $this->actingAs($admin)->putJson(route('admin.podstrony.bloki.update', $page), ['title' => 'Dziecko', 'blocks' => []])->assertOk();
        $this->assertSame($other->id, $page->fresh()->parent_id);
    }

    public function test_shortcodes_are_detected_and_removed_from_excerpts(): void
    {
        $this->assertTrue(ShortcodeParser::has('<p>[podstrony:poziomy=2]</p>'));
        $this->assertTrue(ShortcodeParser::has('[aktualnosci]'));
        $this->assertSame('Wstęp', meta_excerpt('<p>Wstęp</p><p>[podstrony:wyglad=kafle]</p>'));
    }
}
