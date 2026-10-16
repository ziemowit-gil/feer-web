<?php

namespace Tests\Feature;

use App\Support\BlockRenderer;
use Tests\TestCase;

/**
 * „Nowy zestaw kafelków / Nowe przyciski CTA / Nowy akordeon / Nowa ramka informacyjna / Nowy cennik”
 * są osobnymi blokami w kategorii „Moduły” edytora blokowego: wstawienie otwiera kreator.
 */
class BlockEditorBuilderBlocksTest extends TestCase
{
    public function test_nowe_moduly_z_kreatora_sa_blokami(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        foreach (['tileset' => 'Nowy zestaw kafelków', 'cta' => 'Nowe przyciski CTA', 'accordion' => 'Nowy akordeon', 'callout' => 'Nowa ramka informacyjna', 'pricing' => 'Nowy cennik'] as $kind => $title) {
            $this->assertMatchesRegularExpression("/as: 'shortcode', title: '{$title}'[^\\n]*cat: 'modules'[^\\n]*builder: '{$kind}'/u", $js, $kind);
        }
        // Wstawiony blok od razu otwiera kreator (atrybut „builder” jest jednorazowy).
        $this->assertStringContainsString('if (b.attrs.builder) {', $js);
    }

    public function test_atrybut_builder_nie_trafia_do_zapisanych_blokow(): void
    {
        $blocks = BlockRenderer::normalize([['type' => 'shortcode', 'attrs' => ['code' => '[blok:5]', 'builder' => 'callout']]]);

        $this->assertSame(['code' => '[blok:5]'], $blocks[0]['attrs']);
    }
}
