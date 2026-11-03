<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona kontaktu: numer telefonu, informacja o oddzwanianiu z komórek i link do książki telefonicznej. */
class ContactPhoneBookNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_every_contact_layout_shows_switchboard_callback_hint_and_phone_book_link(): void
    {
        // Numer nie jest nazywany „centralą”: etykieta to „Telefon”.
        Page::create(['title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'is_published' => true]);

        foreach (['classic', 'card', 'split', 'tabs', 'feer'] as $layout) {
            SiteSetting::current()->update(['contact_phone' => '12 345 67 89', 'contact_layout' => $layout, 'site_template' => $layout === 'feer' ? 'feer' : 'default']);
            $html = $this->get('/kontakt')->assertOk()->getContent();

            $this->assertStringContainsString('phone-book-note', $html, $layout);
            $this->assertStringNotContainsString('entral', substr($html, strpos($html, 'phone-book-note'), 1800), $layout);
            if ($layout === 'feer') {
                // Szablon FEER: dane kontaktowe to zwykły tekst, bez linków tel:.
                $this->assertStringNotContainsString('tel:123456789', $html, $layout);
            } else {
                $this->assertStringContainsString('href="tel:123456789"', $html, $layout);
            }
            $this->assertStringContainsString('Oddzwaniamy najczęściej z komórek', $html, $layout);
            $this->assertStringContainsString('/ksiazka-telefoniczna"', $html, $layout);
            // Numer telefonu stoi nad informacją o oddzwanianiu i linkiem do książki.
            $note = substr($html, strpos($html, 'phone-book-note'));
            $this->assertLessThan(strpos($note, 'Oddzwaniamy najczęściej'), strpos($note, '12 345 67 89'), $layout);
            $this->assertLessThan(strpos($note, '/ksiazka-telefoniczna"'), strpos($note, 'Oddzwaniamy najczęściej'), $layout);
        }
    }

    public function test_feer_template_shows_note_and_link_hidden_without_phone_book_page(): void
    {
        SiteSetting::current()->update(['contact_phone' => '601 350 487', 'site_template' => 'feer']);
        $html = $this->get('/kontakt')->assertOk()->getContent();

        $this->assertStringContainsString('phone-book-note', $html);
        $this->assertStringContainsString('Oddzwaniamy najczęściej z komórek', $html);
        $this->assertStringNotContainsString('sprawdź, kto i w jakiej sprawie dzwoni', $html);

        Page::create(['title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'is_published' => true]);
        $this->get('/kontakt')->assertSee('sprawdź, kto i w jakiej sprawie dzwoni');
    }

    public function test_no_note_without_phone_and_without_book(): void
    {
        SiteSetting::current()->update(['contact_phone' => '']);

        $this->get('/kontakt')->assertOk()->assertDontSee('phone-book-note', false);
    }
}
