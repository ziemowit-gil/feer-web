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

    public function test_card_layout_shows_office_phone_and_email_on_top(): void
    {
        $this->useLayout('card', [
            'contact_office_address' => 'ul. Przykładowa 10/3',
            'contact_office_city' => '30-001 Kraków',
            'contact_office_building' => 'Biurowiec HEXAGON',
        ]);

        $this->get('/kontakt')->assertOk()
            ->assertSee('Biuro / korespondencja')
            ->assertSee('Biurowiec HEXAGON')
            ->assertSee('+48 123 456 789')
            ->assertSee('kontakt@example.test')
            ->assertSee('Napisz do nas')
            ->assertSee('Wyślij wiadomość');
    }

    public function test_card_layout_uses_the_office_photo_as_hero_background(): void
    {
        $this->useLayout('card');

        // Bez zdjęcia nagłówek jest jednolity — brak klasy tła i przyciemnienia.
        $this->get('/kontakt')->assertOk()->assertDontSee('contact-hero-photo', false);

        $settings = SiteSetting::current();
        $settings->addMediaFromString('udawane-zdjecie')
            ->usingFileName('biuro.jpg')
            ->toMediaCollection('office_photo');

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $this->get('/kontakt')->assertOk()
            ->assertSee('contact-hero-photo', false)
            // Przyciemnienie 65% czerni daje białemu tekstowi min. 7:1 (WCAG 1.4.3).
            ->assertSee('bg-black/65', false);
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
