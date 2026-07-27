<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BipMoveAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_disabled_bip_move_page_shows_unavailable_instead_of_content(): void
    {
        Page::create([
            'title' => 'Dokumenty', 'slug' => 'dokumenty', 'type' => 'bip_move',
            'is_published' => true, 'is_disabled' => true,
            'disabled_message' => 'Strona chwilowo niedostępna.',
        ]);

        $this->get('/dokumenty')
            ->assertOk()
            ->assertSee('Strona chwilowo niedostępna.')
            ->assertDontSee('Biuletyn Informacji Publicznej');
    }

    public function test_wip_full_bip_move_page_hides_content(): void
    {
        Page::create([
            'title' => 'Dokumenty', 'slug' => 'dokumenty', 'type' => 'bip_move',
            'is_published' => true, 'wip_mode' => 'full',
        ]);

        $this->get('/dokumenty')->assertOk()->assertDontSee('Biuletyn Informacji Publicznej');
    }
}
