<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Oznaczenie instalacji podglądowej (APP_PREVIEW_BAR, APP_WATERMARK): pasek i znak wodny tylko, gdy ustawione. */
class DevEnvironmentMarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_bez_ustawien_nie_ma_paska_ani_znaku_wodnego(): void
    {
        config(['app.preview_bar' => null, 'app.watermark' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('dev-preview-bar', $html);
        $this->assertStringNotContainsString('dev-watermark', $html);
    }

    public function test_pasek_wersji_podgladowej_i_znak_wodny(): void
    {
        config(['app.preview_bar' => 'Wersja podglądowa', 'app.watermark' => 'ZG']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="dev-preview-bar no-print" role="region"', $html);
        $this->assertStringContainsString('Wersja podglądowa', $html);
        $this->assertStringContainsString('class="dev-watermark" aria-hidden="true"', $html);
        $this->assertStringContainsString('%3EZG%3C%2Ftext%3E', $html, 'Napis znaku wodnego w SVG.');
    }
}
