<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Ramki z edytora („Ramka”, „Notatka / ostrzeżenie”, „Ważna informacja”) są płaskie jak komunikat
 * „Treść archiwalna”: bez obramowań, wyróżnione tłem, tekst w kolorze ink.
 */
class FlatEditorCalloutsTest extends TestCase
{
    public function test_ramki_z_edytora_sa_plaskie(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        foreach (['content-box', 'content-note', 'content-important'] as $class) {
            $this->assertMatchesRegularExpression('/\.prose \.'.$class.',\s*\.'.$class.' \{([^}]*)\}/', $css, $class);
            preg_match('/\.prose \.'.$class.',\s*\.'.$class.' \{([^}]*)\}/', $css, $m);
            $this->assertStringNotContainsString('border:', $m[1], $class);
            $this->assertStringNotContainsString('border-left', $m[1], $class);
            $this->assertStringContainsString('background-color', $m[1], $class);
            $this->assertStringContainsString('color: var(--color-ink)', $m[1], $class);
        }
    }
}
