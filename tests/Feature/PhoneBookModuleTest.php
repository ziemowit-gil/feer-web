<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moduł „Książka telefoniczna”: panel (osoby i ustawienia), strona /ksiazka-telefoniczna, skrót i link w kontakcie. */
class PhoneBookModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flush();
    }

    private function flush(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function entry(array $a = []): PhoneBookEntry
    {
        return PhoneBookEntry::create($a + ['name' => 'Anna Kowalska', 'phone' => '601 350 487', 'group' => 'Biuro', 'topic' => 'Zapisy na szkolenia']);
    }

    public function test_module_is_registered_and_menu_lists_it(): void
    {
        $this->assertArrayHasKey('phone_book', SiteSetting::MODULES);
        $this->assertArrayHasKey('phone_book', SiteSetting::MODULE_META);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('Książka telefoniczna');
    }

    public function test_admin_crud_and_validation(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.ksiazka-telefoniczna.index'))->assertOk()->assertSee('Książka jest pusta');
        $this->actingAs($admin)->get(route('admin.ksiazka-telefoniczna.create'))->assertOk()->assertSee('W jakiej sprawie się kontaktować');

        $this->actingAs($admin)->post(route('admin.ksiazka-telefoniczna.store'), [
            'name' => 'Jan Nowak', 'phone' => '500 100 200', 'group' => ' Finanse ', 'topic' => 'Faktury', 'hours' => 'pon.–śr.', 'is_active' => '1',
        ])->assertRedirect(route('admin.ksiazka-telefoniczna.index'))->assertSessionHasNoErrors();

        $entry = PhoneBookEntry::first();
        $this->assertSame('Finanse', $entry->group);
        $this->assertTrue($entry->is_active);

        $this->actingAs($admin)->put(route('admin.ksiazka-telefoniczna.update', $entry), ['name' => 'Jan Nowak', 'phone' => '500 100 201', 'order' => 3])
            ->assertRedirect()->assertSessionHasNoErrors();
        $entry->refresh();
        $this->assertSame('500 100 201', $entry->phone);
        $this->assertFalse($entry->is_active);
        $this->assertSame(3, $entry->order);

        $this->actingAs($admin)->post(route('admin.ksiazka-telefoniczna.store'), ['name' => 'X', 'phone' => 'abc'])->assertSessionHasErrors('phone');
        $this->actingAs($admin)->post(route('admin.ksiazka-telefoniczna.store'), ['phone' => '600 000 000'])->assertSessionHasErrors('name');

        $this->actingAs($admin)->delete(route('admin.ksiazka-telefoniczna.destroy', $entry))->assertRedirect();
        $this->assertSame(0, PhoneBookEntry::count());
    }

    public function test_admin_saves_page_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.ksiazka-telefoniczna.ustawienia'), [
            'phonebook_title' => 'Telefony do zespołu', 'phonebook_lead' => 'Dzwoń w dni robocze.', 'phonebook_layout' => 'cards', 'phonebook_sort' => 'alpha', 'phonebook_hide_numbers' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->flush();
        $s = SiteSetting::current();
        $this->assertSame('Telefony do zespołu', $s->phonebook_title);
        $this->assertSame('cards', $s->phonebook_layout);
        $this->assertTrue((bool) $s->phonebook_hide_numbers);

        $this->actingAs($admin)->put(route('admin.ksiazka-telefoniczna.ustawienia'), ['phonebook_layout' => 'siatka', 'phonebook_sort' => 'manual'])
            ->assertSessionHasErrors('phonebook_layout');
    }

    public function test_public_page_shows_active_entries_as_tables_with_title_and_lead(): void
    {
        $this->entry();
        $this->entry(['name' => 'Jan Nowak', 'group' => 'Finanse', 'phone' => '500 100 200']);
        $this->entry(['name' => 'Ukryty Wpis', 'is_active' => false]);
        SiteSetting::current()->update(['phonebook_title' => 'Telefony do zespołu', 'phonebook_lead' => 'Dzwoń w dni robocze.']);
        $this->flush();

        $html = $this->get('/ksiazka-telefoniczna')->assertOk()
            ->assertSee('Telefony do zespołu')->assertSee('Dzwoń w dni robocze.')
            ->assertSee('Anna Kowalska')->assertSee('Jan Nowak')->assertSee('Zapisy na szkolenia')
            ->assertSee('601 350 487')->assertDontSee('href="tel:', false)
            ->assertDontSee('Ukryty Wpis')
            ->getContent();

        // Domyślny widok: lista osób w działach (po jednej liście na dział).
        $this->assertSame(2, substr_count($html, 'class="pb-list pb-list--rows"'));
    }

    public function test_module_can_be_disabled(): void
    {
        $this->entry();
        SiteSetting::current()->update(['disabled_modules' => ['phone_book']]);
        $this->flush();

        $this->get('/ksiazka-telefoniczna')->assertNotFound();
    }

    public function test_published_page_with_the_same_slug_takes_precedence(): void
    {
        $this->entry();
        Page::create(['title' => 'Własna książka', 'slug' => 'ksiazka-telefoniczna', 'type' => 'standard', 'content' => '<p>Treść własnej strony</p>', 'is_published' => true]);

        $this->get('/ksiazka-telefoniczna')->assertOk()->assertSee('Treść własnej strony')->assertDontSee('Anna Kowalska');
    }

    public function test_shortcode_embeds_the_list_in_a_page(): void
    {
        $this->entry();
        Page::create(['title' => 'Zespół', 'slug' => 'zespol', 'type' => 'standard', 'content' => '<p>[ksiazka-telefoniczna]</p>', 'is_published' => true]);

        $this->get('/zespol')->assertOk()->assertSee('Anna Kowalska')->assertSee('data-phone-book', false)->assertDontSee('[ksiazka-telefoniczna]', false);

        SiteSetting::current()->update(['disabled_modules' => ['phone_book']]);
        $this->flush();
        $this->get('/zespol')->assertOk()->assertDontSee('Anna Kowalska');
    }

    public function test_contact_page_links_to_the_module_when_it_has_entries(): void
    {
        SiteSetting::current()->update(['contact_phone' => '12 345 67 89', 'phonebook_title' => 'Telefony do zespołu']);
        $this->flush();

        $this->get('/kontakt')->assertOk()->assertDontSee('sprawdź, kto i w jakiej sprawie dzwoni');

        $this->entry();
        $this->get('/kontakt')->assertOk()->assertSee('Telefony do zespołu: sprawdź, kto i w jakiej sprawie dzwoni')->assertSee('/ksiazka-telefoniczna"', false);
    }

    public function test_list_explains_main_number_and_searches_by_digits(): void
    {
        SiteSetting::current()->update(['contact_phone' => '12 210 06 98']);
        $this->flush();
        $this->entry();

        $html = $this->get('/ksiazka-telefoniczna')->assertOk()
            ->assertSee('Numer kontaktowy')
            ->assertSee('Informacje o naszej działalności.')
            ->assertSee('12 210 06 98')
            ->assertSee('sprawdzisz, kto do Ciebie dzwonił')
            ->assertSee('Znajdź osobę, sprawę albo numer')
            ->getContent();

        $this->assertStringContainsString('data-digits="601350487"', $html);
        $this->assertStringNotContainsString('href="tel:', $html);

        SiteSetting::current()->update(['contact_phone' => '']);
        $this->flush();
        $this->get('/ksiazka-telefoniczna')->assertOk()->assertDontSee('Numer kontaktowy z ogólnymi informacjami');
    }
}
