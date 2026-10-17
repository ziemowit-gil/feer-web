<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Edytor blokowy: podglądy elementów z modułów (nieedytowalne w miejscu) mają dymek „Element modułu — edytuj w …”. */
class BlockEditorModuleTipTest extends TestCase
{
    public function test_module_previews_get_tooltip_pointing_to_the_editing_place(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        $this->assertStringContainsString("'Element modułu — edytuj w ' + where", $js);
        // Shortcode'y, listy, kafelki strony i menu działania.
        $this->assertStringContainsString('moduleTip(preview, kindOf(code)[3])', $js);
        $this->assertStringContainsString("newslist: 'module Aktualności", $js);
        $this->assertStringContainsString("'formularzu strony (pole „Kafelki na stronie”)'", $js);
        $this->assertStringContainsString("'formularzu działania'", $js);
        $this->assertStringContainsString("'module Formularze'", $js);
        // WCAG 1.4.13: Esc chowa dymek, czytniki dostają tekst ukryty wizualnie.
        $this->assertStringContainsString('is-tip-dismissed', $js);
        $this->assertStringContainsString('be-sr-only be-modtip-sr', $js);

        $css = file_get_contents(resource_path('css/block-editor.css'));
        $this->assertStringContainsString('.be-modtip:hover > .be-modtip-bubble { display: block; }', $css);
        $this->assertStringContainsString('.be-modtip.is-tip-dismissed > .be-modtip-bubble { display: none; }', $css);
    }
}
