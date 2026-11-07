<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Edytor blokowy: zaznaczanie kilku bloków naraz (przeciągnięcie myszką, Shift+klik, Shift+strzałki) — statycznie, bez przeglądarki. */
class BlockEditorMultiSelectTest extends TestCase
{
    public function test_zaznaczanie_kilku_blokow_myszka_i_klawiatura(): void
    {
        $js = file_get_contents(base_path('resources/js/block-editor.js'));

        // Przeciągnięcie przez kilka bloków i Shift+klik tworzą zaznaczenie kolejnych bloków z jednej listy.
        $this->assertStringContainsString('function updateMultiFromSelection()', $js);
        $this->assertMatchesRegularExpression('/suggestTimer = setTimeout\(\(\) => \{ updateMultiFromSelection\(\)/', $js);
        $this->assertStringContainsString("if (!e.shiftKey || e.button !== 0", $js);
        $this->assertStringContainsString('function siblingRange(a, b)', $js);
        $this->assertStringContainsString("ra.block.type === 'column'", $js);
        // Z klawiatury: Shift+↑/↓ na bloku w trybie nawigacji.
        $this->assertStringContainsString("(e.key !== 'ArrowDown' && e.key !== 'ArrowUp') || !e.target.classList?.contains('be-block')", $js);
        // Akcje na grupie jako jeden krok historii (pushHistory raz) i komunikaty dla czytnika ekranu.
        foreach (['multiRemove', 'multiDuplicate', 'multiMove'] as $fn) {
            $this->assertMatchesRegularExpression('/function '.$fn.'\(.*?pushHistory\(\)|function '.$fn.'\(.*?insertBlocks\(/s', $js, $fn);
        }
        $this->assertStringContainsString("'aria-label': `Zaznaczone bloki: \${n}`", $js);
        $this->assertStringContainsString("if (e.key === 'Escape' && multi.length)", $js);

        $css = file_get_contents(base_path('resources/css/block-editor.css'));
        $this->assertStringContainsString('.be-block.is-multi', $css);
        $this->assertStringContainsString('.be-multibar', $css);
    }
}
