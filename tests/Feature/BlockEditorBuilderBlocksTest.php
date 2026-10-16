<?php

namespace Tests\Feature;

use App\Support\BlockRenderer;
use Tests\TestCase;

/**
 * Moduły z kreatora (zestaw kafelków, przyciski CTA, akordeon, ramka informacyjna, cennik) są zwykłymi blokami
 * edytora blokowego — „Ramka informacyjna” zastępuje starą „Ramkę”; wstawienie otwiera kreator.
 */
class BlockEditorBuilderBlocksTest extends TestCase
{
    public function test_moduly_z_kreatora_sa_zwyklymi_blokami(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        foreach (['tileset' => ['Nowy zestaw kafelków', 'modules'], 'cta' => ['Przyciski CTA', 'modules'], 'accordion' => ['Akordeon', 'modules'], 'callout' => ['Ramka informacyjna', 'design'], 'pricing' => ['Cennik', 'modules']] as $kind => [$title, $cat]) {
            $this->assertMatchesRegularExpression("/as: 'shortcode', title: '{$title}'[^\\n]*cat: '{$cat}'[^\\n]*builder: '{$kind}'/u", $js, $kind);
        }
        // Wstawiony blok od razu otwiera kreator (atrybut „builder” jest jednorazowy).
        $this->assertStringContainsString('if (b.attrs.builder) {', $js);

        // „Blok z kreatora” i stara „Ramka” nie są już do wstawiania (istniejące nadal się edytuje).
        $this->assertMatchesRegularExpression("/contentblock: \\{[^\\n]*hidden: true,/", $js);
        $this->assertMatchesRegularExpression("/callout: \\{ title: 'Ramka \\(stara\\)'[^\\n]*hidden: true,/u", $js);
        $this->assertStringNotContainsString("'Umieść w ramce'", $js);
        $this->assertStringNotContainsString("wrapChecked('callout')", $js);
    }

    public function test_atrybut_builder_nie_trafia_do_zapisanych_blokow(): void
    {
        $blocks = BlockRenderer::normalize([['type' => 'shortcode', 'attrs' => ['code' => '[blok:5]', 'builder' => 'callout']]]);

        $this->assertSame(['code' => '[blok:5]'], $blocks[0]['attrs']);
    }
}
