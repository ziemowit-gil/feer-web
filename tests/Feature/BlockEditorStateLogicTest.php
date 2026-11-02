<?php

namespace Tests\Feature;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Czysta logika edytora blokowego (resources/js/block-editor-state.js): pozycja bloku po cofnięciu/ponowieniu
 * i ustawienia po zapisie. Sprawdzana wykonaniem, wbudowanym `node --test` (bez nowych zależności) — edytora nie
 * uruchamiamy w przeglądarce. Bez `node` w PATH test jest pomijany; ręcznie: node --test tests/js/block-editor-state.test.mjs
 */
class BlockEditorStateLogicTest extends TestCase
{
    public function test_pure_editor_logic_passes_node_tests(): void
    {
        $node = (new ExecutableFinder)->find('node');
        if ($node === null) {
            $this->markTestSkipped('Brak `node` w PATH — uruchom ręcznie: node --test tests/js/block-editor-state.test.mjs');
        }

        $process = new Process([$node, '--test', 'tests/js/block-editor-state.test.mjs'], base_path(), null, null, 120);
        $process->run();

        $this->assertTrue($process->isSuccessful(), "node --test nie przeszedł:\n".$process->getOutput().$process->getErrorOutput());
        $this->assertMatchesRegularExpression('/\bfail 0\b/', $process->getOutput(), 'Raport node --test nie zawiera podsumowania bez błędów.');
    }

    public function test_editor_script_uses_the_extracted_logic(): void
    {
        $js = file_get_contents(resource_path('js/block-editor.js'));

        $this->assertStringContainsString("import { blockPath, mergeSavedSettings, nearestBlockId } from './block-editor-state.js';", $js);
        $this->assertStringContainsString('export function nearestBlockId(', file_get_contents(resource_path('js/block-editor-state.js')));
        // Moduł jest czysty: bez dostępu do DOM i przeglądarki.
        $this->assertDoesNotMatchRegularExpression('/\b(document|window|navigator|localStorage)\b/', file_get_contents(resource_path('js/block-editor-state.js')));
    }
}
