<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Services\MosaicoImageSizes;
use Tests\TestCase;

/** Wymagane wymiary obrazków w blokach Mosaico: odczyt z szablonu, tabela na pulpicie i etykiety w edytorze. */
class NewsletterImageSizesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_odczytuje_wymiary_z_szablonu_feer_2(): void
    {
        $blocks = collect(MosaicoImageSizes::forTemplate('feer-2'))->keyBy('block');

        $article = $blocks['singleArticleBlock']['rows'][0];
        $this->assertSame([534], $article['widths']);
        $this->assertTrue($article['freeHeight']);
        $this->assertSame([166, 258, 350, 534], $article['options']);

        $columns = $blocks['doubleArticleBlock']['rows'][0];
        $this->assertSame(['lewy', 'prawy'], $columns['fields']);
        $this->assertSame([258], $columns['widths']);
        $this->assertSame([100], $columns['heights']);

        $this->assertArrayNotHasKey('textBlock', $blocks->all()); // bloki bez obrazków pomijamy
        $this->assertSame([], MosaicoImageSizes::forTemplate('../../etc'));
    }

    public function test_pulpit_pokazuje_tabele_wymiarow(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))->assertOk()
            ->assertSee('Wymiary obrazków w blokach Mosaico')
            ->assertSee('Szablon FEER-2')
            ->assertSee('258 × 100 px (stała wysokość)')
            ->assertSee('534 px szer. · wysokość dowolna');
    }

    public function test_edytor_ma_etykiety_wymiarow_i_przelacznik(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.newsletter.mosaico'))->assertRedirect();
        $template = \Modules\Newsletter\Models\NewsletterTemplate::first();

        $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template))->assertOk()
            ->assertSee('id="nl-sizes" aria-pressed="true"', false)
            ->assertSee('function refreshImgSizes', false);
    }

    public function test_pasek_edytora_bez_emoji_i_z_wyborem_szablonu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.newsletter.mosaico'));
        $template = \Modules\Newsletter\Models\NewsletterTemplate::first();
        $template->update(['mosaico_template' => 'feer-1']);

        $html = $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template))->assertOk()->getContent();
        $this->assertStringContainsString('id="nl-template"', $html);
        $this->assertStringNotContainsString('📰', $html);
        $this->assertStringNotContainsString('📐', $html);
        $this->assertMatchesRegularExpression('/<option value="feer-1"\s+selected/', $html);

        $switched = $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template) . '?szablon=feer-2')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<option value="feer-2"\s+selected/', $switched);
        $this->assertStringContainsString('templates\/feer-2\/template-feer-2.html', $switched);

        $this->actingAs($admin)->patchJson(route('admin.newsletter.szablony.editor.save', $template), [
            'metadata' => ['template' => 'x'], 'content' => ['type' => 'template'], 'html' => '<p>x</p>', 'mosaico_template' => 'feer-2',
        ])->assertOk();
        $this->assertSame('feer-2', $template->fresh()->mosaico_template);

        // Zapisany projekt w FEER-1 → przełączenie na FEER-2 wczytuje bloki bez starego motywu (kolory z nowego szablonu).
        $template->update(['mosaico_template' => 'feer-1', 'editor_metadata' => ['template' => 'https://x/vendor/mosaico/templates/feer-1/template-feer-1.html'], 'editor_content' => ['type' => 'template', 'theme' => ['type' => 'theme']]]);
        $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template) . '?szablon=feer-2')->assertOk()->assertSee('if (!true ||', false);
        $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template))->assertOk()->assertSee('if (!false ||', false);

    }

    public function test_wstaw_aktualnosc_sortuje_po_kategorii_i_daje_wiecej_niz_12_pozycji(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = \App\Models\NewsCategory::create(['name' => 'Aktualności', 'slug' => 'aktualnosci', 'order' => 1]);
        $b = \App\Models\NewsCategory::create(['name' => 'Szkolenia', 'slug' => 'szkolenia', 'order' => 2]);
        foreach (range(1, 16) as $i) {
            \App\Models\News::create(['title' => "Wpis {$i}", 'slug' => "wpis-{$i}", 'content' => '<p>x</p>', 'is_published' => true,
                'published_at' => now()->subDays($i), 'news_category_id' => $i % 2 ? $a->id : $b->id]);
        }

        $items = $this->actingAs($admin)->getJson(route('admin.newsletter.content.items', ['source' => 'news']) . '?limit=30&since=all&sort=category')
            ->assertOk()->json('items');

        $this->assertCount(16, $items);
        $cats = array_column($items, 'category');
        $this->assertSame(array_merge(array_fill(0, 8, 'Aktualności'), array_fill(0, 8, 'Szkolenia')), $cats);
        $this->assertSame('Wpis 1', $items[0]['title']); // w grupie od najnowszych

        // Blok w mailu nadal ma limit 12.
        $this->assertCount(12, app(\Modules\Newsletter\Services\ContentFeeder::class)->items('news', ['limit' => 30, 'since' => 'all']));
    }

    public function test_wstaw_z_cms_zwraca_aktywne_banery_graficzne(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        \App\Models\Banner::create(['name' => 'Zbiórka 1,5%', 'type' => 'image', 'image_path' => 'banners/a.png', 'image_alt' => 'Przekaż 1,5%', 'width' => 1200, 'height' => 400, 'link_url' => 'https://feer.org.pl/wsparcie', 'is_active' => true]);
        \App\Models\Banner::create(['name' => 'Wyłączony', 'type' => 'image', 'image_path' => 'banners/b.png', 'is_active' => false]);
        \App\Models\Banner::create(['name' => 'Wygasły', 'type' => 'image', 'image_path' => 'banners/c.png', 'is_active' => true, 'ends_at' => now()->subDay()]);
        \App\Models\Banner::create(['name' => 'HTML', 'type' => 'html', 'html_content' => '<p>x</p>', 'is_active' => true]);

        $items = $this->actingAs($admin)->getJson(route('admin.newsletter.content.items', ['source' => 'banners']))->assertOk()->json('items');

        $this->assertCount(1, $items);
        $this->assertSame('Zbiórka 1,5%', $items[0]['title']);
        $this->assertSame('Przekaż 1,5%', $items[0]['image_alt']);
        $this->assertSame('https://feer.org.pl/wsparcie', $items[0]['url']);
        $this->assertStringEndsWith('/storage/banners/a.png', $items[0]['image']);
        $this->assertStringContainsString('1200 × 400 px', $items[0]['category']);
        $this->assertTrue($items[0]['banner']);

        $this->actingAs($admin)->get(route('admin.newsletter.mosaico'));
        $template = \Modules\Newsletter\Models\NewsletterTemplate::first();
        $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template))->assertOk()
            ->assertSee('<option value="banners">Banery</option>', false)->assertSee('function insertBanner', false);
    }
}
