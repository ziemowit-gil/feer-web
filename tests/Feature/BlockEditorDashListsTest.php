<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Edytor blokowy: wypunktowania pisane myślnikami („- a”, „- b”) zamieniane na listy (wczytanie, wklejanie, import),
 * „- ” na początku akapitu z tekstem → lista, „ - ” między słowami → półpauza.
 */
class BlockEditorDashListsTest extends TestCase
{
    public function test_dash_bullets_become_lists_and_spaced_hyphen_becomes_en_dash(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        $this->assertStringContainsString('function dashLists(list)', $js);
        // Wczytanie strony, treść z klasycznego edytora / wklejony HTML, wklejony tekst i import .txt.
        $this->assertStringContainsString('blocks: dashLists(hydrate(config.blocks))', $js);
        $this->assertStringContainsString('return dashLists(out).reduce(', $js);
        $this->assertStringContainsString("pasteBlocks(b, el, key, [block('list', { items: lines.map(dashes) })])", $js);
        $this->assertStringContainsString('blocks = dashLists(blocks);', $js);
        // Pojedyncza linia z półpauzą (dialog) nie jest zamieniana.
        $this->assertStringContainsString('items.length < 2 && !found[i - 1] && !found[i + 1]', $js);
        // Komunikat z „Cofnij”.
        $this->assertStringContainsString('pisane myślnikami („- …”) na listy', $js);
        // Pisanie: półpauza po spacji i „- ” przed istniejącym tekstem.
        $this->assertStringContainsString("if (e.inputType === 'insertText' && e.data === ' ') typeEnDash();", $js);
        $this->assertStringContainsString("replaceBlock(b.id, [block('list', { items: [el.innerHTML.replace(", $js);
    }
}
