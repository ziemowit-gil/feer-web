<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\PageTypes\PageTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Typ strony „Książka telefoniczna”: kto, numer komórkowy i w jakiej sprawie się kontaktować. */
class PhoneBookPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function book(array $contacts): Page
    {
        return Page::create([
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'is_published' => true,
            'type_data' => ['lead' => 'Dzwoń w dni robocze.', 'contacts' => $contacts],
        ]);
    }

    public function test_type_is_registered_with_rules_and_list_key(): void
    {
        $type = PageTypeRegistry::get('phone_book');

        $this->assertNotNull($type);
        $this->assertTrue($type->usesTypeData());
        $this->assertSame(['contacts'], $type->listKeys());
        $this->assertArrayHasKey('type_data.contacts.*.phone', $type->rules());
    }

    public function test_page_shows_people_grouped_with_plain_text_numbers_and_topics(): void
    {
        $this->book([
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'email' => 'anna@example.org', 'topic' => 'Zapisy na szkolenia'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'role' => 'Księgowy', 'phone' => '+48 500 100 200', 'topic' => 'Faktury i rozliczenia'],
            ['name' => '', 'phone' => '', 'topic' => 'wiersz bez osoby i numeru'],
        ]);

        $this->get('/nasze-numery-telefonow')->assertOk()
            ->assertSee('data-page-type="phone_book"', false)
            ->assertSee('Dzwoń w dni robocze.')
            ->assertSee('Biuro')->assertSee('Finanse')
            ->assertSee('Anna Kowalska')->assertSee('Zapisy na szkolenia')
            ->assertSee('601 350 487')->assertSee('+48 500 100 200')
            ->assertDontSee('href="tel:', false)
            ->assertSee('href="mailto:anna@example.org"', false)
            ->assertDontSee('wiersz bez osoby i numeru');
    }

    public function test_empty_book_shows_hint_and_text_is_escaped(): void
    {
        $this->book([]);
        $this->get('/nasze-numery-telefonow')->assertOk()->assertSee('nie ma jeszcze wpisów');

        $page = Page::where('slug', 'nasze-numery-telefonow')->first();
        $page->update(['type_data' => ['contacts' => [['name' => '<script>x</script>', 'phone' => '600 000 000']]]]);
        $this->get('/nasze-numery-telefonow')->assertOk()->assertDontSee('<script>x</script>', false);
    }

    public function test_admin_form_renders_fields_and_saves_contacts(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN, 'default_editor' => \App\Models\User::EDITOR_CLASSIC]);
        $page = $this->book([['name' => 'Anna Kowalska', 'phone' => '601 350 487', 'topic' => 'Zapisy']]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('data-type-fields="phone_book"', false)
            ->assertSee('name="type_data[contacts][0][phone]"', false)
            ->assertSee('W jakiej sprawie się kontaktować');

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['contacts' => [
                ['name' => 'Jan Nowak', 'phone' => '500 100 200', 'topic' => 'Faktury', 'group' => 'Finanse'],
                ['name' => '', 'phone' => '', 'topic' => ''],
            ]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $contacts = $page->refresh()->typeData()['contacts'];
        $this->assertCount(1, $contacts);
        $this->assertSame('Jan Nowak', $contacts[0]['name']);
        $this->assertSame('Finanse', $contacts[0]['group']);
    }

    private function bookWith(array $typeData): Page
    {
        return Page::create([
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    public function test_hours_group_filter_and_vcard_are_rendered_without_call_links(): void
    {
        $this->bookWith(['contacts' => [
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'email' => 'anna@example.org', 'topic' => 'Zapisy', 'hours' => 'pon.–śr. 10–12'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'phone' => '500 100 200', 'topic' => 'Faktury'],
        ]]);

        $html = $this->get('/nasze-numery-telefonow')->assertOk()->getContent();

        $this->assertStringContainsString('<dt>Kiedy dzwonić:</dt><dd>pon.–śr. 10–12</dd>', $html);
        $this->assertStringContainsString('role="group" aria-label="Filtr działów"', $html);
        // Książka służy do rozpoznania, kto dzwoni: bez linków „zadzwoń” i bez kopiowania numeru.
        $this->assertStringNotContainsString('Kopiuj numer', $html);
        $this->assertStringNotContainsString('href="tel:', $html);
        $this->assertStringContainsString('Zapisz w telefonie', $html);
        $this->assertStringContainsString('BEGIN:VCARD', str_replace('\\r\\n', "\n", $html));
        $this->assertStringContainsString('TEL;TYPE=CELL:601350487', $html);
        $this->assertStringContainsString('anna-kowalska.vcf', $html);
        // Numery widoczne od razu, gdy nie włączono ukrywania.
        $this->assertStringNotContainsString('data-phone-reveal', $html);
    }

    public function test_single_group_has_no_filter_chips(): void
    {
        $this->bookWith(['contacts' => [['group' => 'Biuro', 'name' => 'Anna', 'phone' => '601 350 487']]]);

        $this->get('/nasze-numery-telefonow')->assertOk()->assertDontSee('Filtr działów');
    }

    public function test_alphabetical_sort_orders_groups_and_people(): void
    {
        $this->bookWith(['sort' => 'alpha', 'contacts' => [
            ['group' => 'Zarząd', 'name' => 'Zofia Zet', 'phone' => '600 000 003'],
            ['group' => 'Biuro', 'name' => 'Piotr Pe', 'phone' => '600 000 002'],
            ['group' => 'Biuro', 'name' => 'Adam Ae', 'phone' => '600 000 001'],
        ]]);

        $this->get('/nasze-numery-telefonow')->assertOk()->assertSeeInOrder(['Biuro', 'Adam Ae', 'Piotr Pe', 'Zarząd', 'Zofia Zet']);
    }

    public function test_hide_numbers_wraps_number_in_reveal_with_button(): void
    {
        $this->bookWith(['hide_numbers' => '1', 'contacts' => [['name' => 'Anna Kowalska', 'phone' => '601 350 487']]]);

        $this->get('/nasze-numery-telefonow')->assertOk()
            ->assertSee('phone-reveal phone-reveal--inline', false)
            ->assertSee('data-phone-reveal', false)
            ->assertSee('Pokaż numer: Anna Kowalska')
            ->assertSee('<span class="phone-reveal-number">', false)
            ->assertDontSee('href="tel:', false);
    }

    public function test_admin_saves_new_settings_and_hours(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $page = $this->bookWith(['contacts' => []]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('name="type_data[sort]"', false)
            ->assertSee('name="type_data[hide_numbers]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['sort' => 'alpha', 'hide_numbers' => '1', 'contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'hours' => 'pon.–śr. 10–12']]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $td = $page->refresh()->typeData();
        $this->assertSame('alpha', $td['sort']);
        $this->assertTrue((bool) $td['hide_numbers']);
        $this->assertSame('pon.–śr. 10–12', $td['contacts'][0]['hours']);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['sort' => 'losowo', 'contacts' => []],
        ])->assertSessionHasErrors('type_data.sort');
    }

    public function test_default_layout_is_a_list_per_department(): void
    {
        $this->bookWith(['contacts' => [
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'topic' => 'Zapisy', 'hours' => 'pon.–śr.'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'phone' => '500 100 200', 'topic' => 'Faktury'],
        ]]);

        $html = $this->get('/nasze-numery-telefonow')->assertOk()->getContent();

        // Jeden panel na górze (numer, wyszukiwarka, filtr, licznik) i lista osób w każdym dziale.
        $this->assertSame(2, substr_count($html, 'class="pb-list pb-list--rows"'));
        $this->assertStringContainsString('<h2 id="phonebook-group-0" class="pb-group-h">Biuro <span class="pb-group-n">(1)</span></h2>', $html);
        $this->assertStringContainsString('<p class="pb-name">Anna Kowalska</p>', $html);
        $this->assertStringContainsString('<p class="pb-role">Koordynatorka</p>', $html);
        $this->assertStringContainsString('<dt>W sprawie:</dt><dd>Zapisy</dd>', $html);
        $this->assertStringContainsString('W książce: 2 osoby', $html);
        $this->assertStringContainsString('aria-label="Wyczyść wyszukiwanie"', $html);
        $this->assertStringNotContainsString('<table', $html);
    }

    public function test_cards_layout_and_missing_hours_column(): void
    {
        $this->bookWith(['layout' => 'cards', 'contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'topic' => 'Zapisy', 'hours' => 'pon.–śr.']]]);
        $html = $this->get('/nasze-numery-telefonow')->assertOk()->getContent();
        $this->assertStringContainsString('class="pb-list pb-list--cards"', $html);
        $this->assertStringContainsString('Kiedy dzwonić:', $html);

        // Bez godzin osoba nie ma pustego wiersza „Kiedy dzwonić”.
        Page::where('slug', 'nasze-numery-telefonow')->first()->update(['type_data' => ['contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'topic' => 'Zapisy']]]]);
        $this->get('/nasze-numery-telefonow')->assertOk()->assertDontSee('Kiedy dzwonić:', false);
    }

    public function test_admin_offers_layout_and_rejects_unknown_value(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $page = $this->bookWith(['contacts' => []]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('name="type_data[layout]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['layout' => 'cards', 'contacts' => []],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cards', $page->refresh()->typeData()['layout']);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'nasze-numery-telefonow', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['layout' => 'siatka', 'contacts' => []],
        ])->assertSessionHasErrors('type_data.layout');
    }

    public function test_email_shows_only_for_people_who_have_one(): void
    {
        $this->bookWith(['contacts' => [
            ['name' => 'Anna Kowalska', 'phone' => '601 350 487', 'email' => 'anna@example.org'],
            ['name' => 'Jan Nowak', 'phone' => '500 100 200'],
        ]]);

        $html = $this->get('/nasze-numery-telefonow')->assertOk()->getContent();
        $book = substr($html, strpos($html, 'data-phone-book'));
        $book = substr($book, 0, (int) strpos($book, '</main>'));
        $this->assertSame(1, substr_count($book, 'href="mailto:'));
        $this->assertStringContainsString('<a href="mailto:anna@example.org" class="pb-link">', $html);
    }
}
