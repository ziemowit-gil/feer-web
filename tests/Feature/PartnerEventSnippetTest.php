<?php

namespace Tests\Feature;

use App\Support\ClassicInserts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Wstawka „Wydarzenie partnera” (pomarańczowa ramka z grafiką) w edytorach treści. */
class PartnerEventSnippetTest extends TestCase
{
    use RefreshDatabase;

    public function test_snippet_has_orange_box_with_organizer_notice(): void
    {
        $html = ClassicInserts::SNIPPETS['partnerEvent']['html'];

        $this->assertStringContainsString('partner-event-box', $html);
        $this->assertStringContainsString('Jeżeli masz pytania', $html);
        $this->assertStringContainsString('Nie jesteśmy organizatorem', $html);
        $this->assertContains('partnerEvent', array_column(ClassicInserts::forBlockEditor()['snippets'], 'key'));
    }

    public function test_background_graphic_and_style_exist(): void
    {
        $this->assertFileExists(public_path('img/partner-event-info.svg'));
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.partner-event-box', $css);
        $this->assertStringContainsString('/img/partner-event-info.svg', $css);
        // Tekst #1D1D1A na tle ramki #F5A524 ma kontrast 8,3:1 (na #EA8F00 było 6,8:1 — za mało na 7:1).
        $this->assertStringContainsString('background: #f5a524 url("/img/partner-event-info.svg")', $css);
        $this->assertStringContainsString('color: #1d1d1a;', $css);
    }

    public function test_block_renderer_outputs_partner_event_block(): void
    {
        $html = (new \App\Support\BlockRenderer)->render([[
            'id' => 'a', 'type' => 'partnerevent', 'attrs' => ['text' => 'Nie jesteśmy organizatorem.'], 'inner' => [],
        ]]);

        $this->assertStringContainsString('<div class="partner-event-box"><p class="partner-event-tag">Materiał partnera – innej organizacji</p>', $html);
        $this->assertStringContainsString('Nie jesteśmy organizatorem.', $html);
    }
}
