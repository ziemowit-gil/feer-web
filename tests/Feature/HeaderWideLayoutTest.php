<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderWideLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function wide(string $layout): void
    {
        SiteSetting::current()->forceFill([
            'header_layout' => 'wide_mission',
            'wide_mission_layout' => $layout,
            'bank_account_number' => 'PL 11 2222 3333 4444 5555 6666 7777',
            'wide_mission_cta_label' => 'Materiały edukacyjne',
            'wide_mission_cta_url' => '/materialy',
        ])->save();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_right_layout_keeps_account_in_the_right_column(): void
    {
        $this->wide('right');

        $html = $this->get('/kontakt')->assertOk()
            ->assertSee('PL 11 2222 3333 4444 5555 6666 7777')
            ->assertSee('Materiały edukacyjne')
            ->getContent();

        // Bez osobnego paska wsparcia nad belką.
        $this->assertStringNotContainsString('bg-brand-light/50', $html);
    }

    public function test_bar_layout_moves_account_above_the_bar(): void
    {
        $this->wide('bar');

        $html = $this->get('/kontakt')->assertOk()
            ->assertSee('PL 11 2222 3333 4444 5555 6666 7777')
            ->assertSee('Materiały edukacyjne')
            ->getContent();

        // Numer konta stoi w pasku nad belką, a nie w prawej kolumnie.
        $barStart = strpos($html, 'bg-brand-light/50');
        $this->assertNotFalse($barStart, 'Brak paska wsparcia nad belką.');
        $this->assertStringContainsString(
            'PL 11 2222 3333 4444 5555 6666 7777',
            substr($html, $barStart, 800),
        );

        // Poza paskiem zostaje już tylko wersja mobilna (blok lg:hidden).
        $this->assertSame(2, substr_count($html, 'PL 11 2222 3333 4444 5555 6666 7777'));
    }

    public function test_unknown_wide_layout_falls_back_to_right(): void
    {
        $this->wide('nie-ma-takiego');

        $this->assertSame('right', SiteSetting::current()->wideMissionLayoutValue());
        $this->get('/kontakt')->assertOk()->assertDontSee('bg-brand-light/50', false);
    }
}
