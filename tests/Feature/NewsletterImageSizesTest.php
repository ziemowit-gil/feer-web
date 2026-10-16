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
}
