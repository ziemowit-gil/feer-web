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
}
