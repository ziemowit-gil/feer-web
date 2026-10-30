<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona „Dołącz do nas”: płaski wygląd (bez ramek na kartach ofert), kolumny od tej samej linii, krótkie teksty. */
class JoinUsPageLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_strona_ma_plaskie_karty_i_wyrownane_kolumny(): void
    {
        $html = $this->get('/dolacz-do-nas')->assertOk()->getContent();

        $this->assertStringContainsString('.jn-main > .jn-sec:first-child { margin-top: 0; }', $html);
        $this->assertStringNotContainsString('border: 2px solid #d1d5db', $html, 'karta oferty bez ramki');
        $this->assertStringNotContainsString('border: 2px dashed', $html, 'pusty stan bez przerywanej ramki');
        $this->assertStringNotContainsString('border: 2px solid currentColor', $html, 'licznik w kaflu bez obwódki');
        $this->assertStringNotContainsString('każda forma jest cenna', $html);
    }
}
