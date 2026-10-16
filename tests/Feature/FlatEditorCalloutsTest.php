<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Support\ShortcodeParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ramki z edytora („Ramka”, „Notatka / ostrzeżenie”, „Ważna informacja”) są płaskie jak komunikat
 * „Treść archiwalna”: bez obramowań, wyróżnione tłem, tekst w kolorze ink.
 */
class FlatEditorCalloutsTest extends TestCase
{
    use RefreshDatabase;

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

    /** Blok „Ramka informacyjna” z kreatora ([blok:ID]): bez obramowania, ikona po lewej, przed treścią. */
    public function test_ramka_informacyjna_z_kreatora_jest_plaska(): void
    {
        $block = ContentBlock::create(['type' => 'callout', 'name' => 'Uwaga', 'data' => ['title' => 'Termin', 'text' => 'Zgłoszenia do piątku', 'variant' => 'red', 'negative' => false, 'icon' => 'warning']]);

        $html = ShortcodeParser::render('[blok:'.$block->id.']');

        $this->assertStringContainsString('Zgłoszenia do piątku', $html);
        $this->assertDoesNotMatchRegularExpression('/\.cx-[a-z.-]*[^{]*\{[^}]*border(-color|-width|:)/', $html);
        $this->assertLessThan(strpos($html, 'class="cx-title'), strpos($html, 'class="cx-ico'));
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}
