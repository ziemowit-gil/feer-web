<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function useLayout(string $layout, array $attributes = []): void
    {
        SiteSetting::current()->forceFill(array_merge([
            'contact_layout' => $layout,
            'contact_email' => 'kontakt@example.test',
            'contact_phone' => '+48 123 456 789',
            'contact_address' => 'ul. Testowa 1',
            'contact_city' => '00-001 Testowo',
        ], $attributes))->save();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_classic_layout_renders_form_and_details(): void
    {
        $this->useLayout('classic');

        $this->get('/kontakt')->assertOk()
            ->assertSee('Kontakt')
            ->assertSee('Wyślij wiadomość')
            ->assertSee('kontakt@example.test')
            ->assertDontSee('Napisz e-mail');
    }

    public function test_split_layout_renders_quick_tiles_and_the_same_form(): void
    {
        $this->useLayout('split');

        $this->get('/kontakt')->assertOk()
            ->assertSee('Napisz e-mail')
            ->assertSee('Zadzwoń')
            ->assertSee('Odwiedź nas')
            ->assertSee('Wyślij wiadomość')
            ->assertSee('kontakt@example.test');
    }

    public function test_unknown_layout_falls_back_to_classic(): void
    {
        $this->useLayout('nie-ma-takiego');

        $this->get('/kontakt')->assertOk()->assertDontSee('Napisz e-mail');
    }

    public function test_correspondence_note_renders_once_per_page(): void
    {
        $this->useLayout('split', [
            'contact_correspondence_note' => 'Korespondencję prosimy kierować na adres biura.',
        ]);

        $response = $this->get('/kontakt')->assertOk()
            ->assertSee('Korespondencję prosimy kierować na adres biura.');

        $this->assertSame(1, substr_count($response->getContent(), 'Korespondencję prosimy kierować na adres biura.'));
    }
}
