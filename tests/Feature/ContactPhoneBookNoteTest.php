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
        // Numer nie jest nazywany „centralą”: etykieta to „Numer kontaktowy”.
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
            $this->assertStringContainsString('Pracujemy zdalnie, dlatego', $html, $layout);
            $this->assertStringContainsString('/ksiazka-telefoniczna"', $html, $layout);
            // Numer telefonu stoi nad informacją o oddzwanianiu i linkiem do książki.
            $note = substr($html, strpos($html, 'phone-book-note'));
            $this->assertLessThan(strpos($note, 'Pracujemy zdalnie'), strpos($note, '12 345 67 89'), $layout);
            $this->assertLessThan(strpos($note, '/ksiazka-telefoniczna"'), strpos($note, 'Pracujemy zdalnie'), $layout);
        }
    }

    public function test_feer_template_shows_note_and_link_hidden_without_phone_book_page(): void
    {
        SiteSetting::current()->update(['contact_phone' => '601 350 487', 'site_template' => 'feer']);
        $html = $this->get('/kontakt')->assertOk()->getContent();

        $this->assertStringContainsString('phone-book-note', $html);
        $this->assertStringContainsString('Pracujemy zdalnie, dlatego', $html);
        $this->assertStringNotContainsString('sprawdź, kto i w jakiej sprawie dzwoni', $html);

        Page::create(['title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'is_published' => true]);
        $this->get('/kontakt')->assertSee('sprawdź, kto i w jakiej sprawie dzwoni');
    }

    public function test_no_note_without_phone_and_without_book(): void
    {
        SiteSetting::current()->update(['contact_phone' => '']);

        $this->get('/kontakt')->assertOk()->assertDontSee('phone-book-note', false);
    }

    public function test_phone_hours_are_shown_under_the_number_in_every_layout_with_normalized_dashes(): void
    {
        foreach (['classic', 'card', 'split', 'tabs', 'feer'] as $layout) {
            SiteSetting::current()->update([
                'contact_phone' => '12 345 67 89', 'contact_phone_hours' => 'czw-pt 10:00 - 16', 'contact_layout' => $layout,
                'site_template' => $layout === 'feer' ? 'feer' : 'default',
            ]);
            $html = $this->get('/kontakt')->assertOk()->getContent();
            $note = substr($html, strpos($html, 'phone-book-note'));

            $this->assertStringContainsString('Kiedy dzwonić:</span> czw–pt 10:00–16', $note, $layout);
            $this->assertLessThan(strpos($note, 'Kiedy dzwonić'), strpos($note, '12 345 67 89'), $layout);
        }

        SiteSetting::current()->update(['contact_phone_hours' => '']);
        $this->get('/kontakt')->assertOk()->assertDontSee('Kiedy dzwonić:', false);
    }

    public function test_hours_are_not_shown_without_a_phone_number(): void
    {
        SiteSetting::current()->update(['contact_phone' => '', 'contact_phone_hours' => 'czw-pt 10-16']);

        $this->get('/kontakt')->assertOk()->assertDontSee('Kiedy dzwonić:', false);
    }

    public function test_admin_edits_phone_hours_in_the_contact_settings(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $page = \App\Models\Page::firstOrCreate(['type' => 'contact'], ['title' => 'Kontakt', 'slug' => 'kontakt-2', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('name="contact_phone_hours"', false)->assertSee('placeholder="np. czw-pt 10:00 - 16"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'parent_id' => '',
            'contact_address' => 'ul. Testowa 1', 'contact_city' => '00-001 Warszawa', 'contact_email' => 'kontakt@example.pl',
            'contact_phone' => '601 350 487', 'contact_phone_hours' => 'czw-pt 10:00 - 16',
        ])->assertRedirect();

        $this->assertSame('czw-pt 10:00 - 16', SiteSetting::current()->refresh()->contact_phone_hours);
    }

    public function test_contact_number_is_described_as_general_information_line(): void
    {
        SiteSetting::current()->update(['contact_phone' => '12 210 06 98']);

        $this->get('/kontakt')->assertOk()
            ->assertSee('Numer kontaktowy')
            ->assertSee('Ogólne informacje o naszej działalności.')
            ->assertDontSee('Numer ogólny');

        SiteSetting::current()->update(['contact_phone' => '']);
        $this->get('/kontakt')->assertOk()->assertDontSee('Ogólne informacje o naszej działalności.');
    }
}
