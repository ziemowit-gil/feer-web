<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Edytor blokowy: kopiowanie/wycinanie/wklejanie całych bloków oraz plus do wstawiania bloku „w miejscu”. */
class BlockEditorBlockClipboardTest extends TestCase
{
    public function test_blocks_can_be_copied_cut_and_pasted_from_navigation_mode_and_menu(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        $this->assertStringContainsString("const BLOCK_CLIP = 'application/x-feer-blocks'", $js);
        $this->assertStringContainsString("canvas.addEventListener('copy'", $js);
        $this->assertStringContainsString("canvas.addEventListener('cut'", $js);
        $this->assertStringContainsString("canvas.addEventListener('paste'", $js);
        // Tylko gdy fokus jest na samym bloku — w tekście działa zwykły schowek.
        $this->assertStringContainsString("w.classList?.contains('be-block')", $js);
        // Menu bloku i lista skrótów.
        $this->assertStringContainsString("label: 'Kopiuj blok'", $js);
        $this->assertStringContainsString("label: 'Wytnij blok'", $js);
        $this->assertStringContainsString("label: 'Wklej blok po'", $js);
        $this->assertStringContainsString("'W trybie nawigacji (Esc): kopiuj, wytnij, wklej cały blok'", $js);
        // Komunikat dla czytników ekranu.
        $this->assertStringContainsString("announce('Wklejono blok: '", $js);
    }

    public function test_plus_button_between_blocks_is_pointer_only_with_keyboard_equivalents(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        $this->assertStringContainsString("class: 'be-between', tabindex: '-1', 'aria-hidden': 'true'", $js);
        // Równoważniki z klawiatury zostają: menu bloku „Wstaw blok po” i „/”.
        $this->assertStringContainsString("label: 'Wstaw blok po'", $js);

        $css = file_get_contents(resource_path('css/block-editor.css'));
        $this->assertStringContainsString('.be-block:hover:not(:has(.be-block:hover)) > .be-between', $css);
        $this->assertStringContainsString('.be-block.is-selected > .be-between { display: inline-flex; }', $css);
    }
}
