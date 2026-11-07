<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Edytor blokowy: podpowiedź „zamień na listę” dla zaznaczonych linii zaczynających się od „-” albo „1.” (statycznie, bez przeglądarki). */
class BlockEditorListSuggestionTest extends TestCase
{
    public function test_zaznaczenie_linii_wypunktowania_podpowiada_liste(): void
    {
        $js = file_get_contents(base_path('resources/js/block-editor.js'));

        $this->assertStringContainsString('function listCandidate()', $js);
        $this->assertStringContainsString('function applyListSuggestion(', $js);
        // Wyzwalane zmianą zaznaczenia, tylko dla dwóch i więcej linii; punktory i numeracja nie mieszają się.
        $this->assertMatchesRegularExpression('/selectionchange.*?scheduleListSuggest\(\)/s', $js);
        $this->assertStringContainsString('if (picked.length < 2) return null;', $js);
        $this->assertStringContainsString('NUMBER_LINE.test(t)', $js);
        $this->assertStringContainsString('BULLET_LINE.test(t)', $js);
        // Dostępność: komunikat dla czytnika, skrót Alt+L i Escape zamykający podpowiedź.
        $this->assertStringContainsString("e.code === 'KeyL'", $js);
        $this->assertStringContainsString('Naciśnij Alt+L, aby zamienić go na', $js);
        $this->assertStringContainsString("role: 'group', 'aria-label': 'Podpowiedź: zamiana na listę'", $js);
        $this->assertStringContainsString('suggestDismissed = suggestSig;', $js);
        // Zamiana jest cofalna (pushHistory przed zmianą).
        $this->assertMatchesRegularExpression('/function applyListSuggestion.*?pushHistory\(\);.*?renderCanvas\(\);/s', $js);
        $this->assertStringContainsString('.be-suggest', file_get_contents(base_path('resources/css/block-editor.css')));
    }
}
