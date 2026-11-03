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
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'is_published' => true,
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

    public function test_page_shows_people_grouped_with_tel_links_and_topics(): void
    {
        $this->book([
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'email' => 'anna@example.org', 'topic' => 'Zapisy na szkolenia'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'role' => 'Księgowy', 'phone' => '+48 500 100 200', 'topic' => 'Faktury i rozliczenia'],
            ['name' => '', 'phone' => '', 'topic' => 'wiersz bez osoby i numeru'],
        ]);

        $this->get('/ksiazka-telefoniczna')->assertOk()
            ->assertSee('data-page-type="phone_book"', false)
            ->assertSee('Dzwoń w dni robocze.')
            ->assertSee('Biuro')->assertSee('Finanse')
            ->assertSee('Anna Kowalska')->assertSee('Zapisy na szkolenia')
            ->assertSee('href="tel:601350487"', false)
            ->assertSee('href="tel:+48500100200"', false)
            ->assertSee('href="mailto:anna@example.org"', false)
            ->assertDontSee('wiersz bez osoby i numeru');
    }

    public function test_empty_book_shows_hint_and_text_is_escaped(): void
    {
        $this->book([]);
        $this->get('/ksiazka-telefoniczna')->assertOk()->assertSee('nie ma jeszcze wpisów');

        $page = Page::where('slug', 'ksiazka-telefoniczna')->first();
        $page->update(['type_data' => ['contacts' => [['name' => '<script>x</script>', 'phone' => '600 000 000']]]]);
        $this->get('/ksiazka-telefoniczna')->assertOk()->assertDontSee('<script>x</script>', false);
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
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
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
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    public function test_hours_group_filter_copy_and_vcard_are_rendered(): void
    {
        $this->bookWith(['contacts' => [
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'email' => 'anna@example.org', 'topic' => 'Zapisy', 'hours' => 'pon.–śr. 10–12'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'phone' => '500 100 200', 'topic' => 'Faktury'],
        ]]);

        $html = $this->get('/ksiazka-telefoniczna')->assertOk()->getContent();

        $this->assertStringContainsString('<th scope="col">Kiedy dzwonić</th>', $html);
        $this->assertStringContainsString('pon.–śr. 10–12', $html);
        $this->assertStringContainsString('role="group" aria-label="Filtr działów"', $html);
        $this->assertStringContainsString('Kopiuj numer', $html);
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

        $this->get('/ksiazka-telefoniczna')->assertOk()->assertDontSee('Filtr działów');
    }

    public function test_alphabetical_sort_orders_groups_and_people(): void
    {
        $this->bookWith(['sort' => 'alpha', 'contacts' => [
            ['group' => 'Zarząd', 'name' => 'Zofia Zet', 'phone' => '600 000 003'],
            ['group' => 'Biuro', 'name' => 'Piotr Pe', 'phone' => '600 000 002'],
            ['group' => 'Biuro', 'name' => 'Adam Ae', 'phone' => '600 000 001'],
        ]]);

        $this->get('/ksiazka-telefoniczna')->assertOk()->assertSeeInOrder(['Biuro', 'Adam Ae', 'Piotr Pe', 'Zarząd', 'Zofia Zet']);
    }

    public function test_hide_numbers_wraps_number_in_reveal_with_button(): void
    {
        $this->bookWith(['hide_numbers' => '1', 'contacts' => [['name' => 'Anna Kowalska', 'phone' => '601 350 487']]]);

        $this->get('/ksiazka-telefoniczna')->assertOk()
            ->assertSee('phone-reveal phone-reveal--inline', false)
            ->assertSee('data-phone-reveal', false)
            ->assertSee('Pokaż numer: Anna Kowalska')
            ->assertSee('href="tel:601350487" class="phone-reveal-number"', false);
    }

    public function test_admin_saves_new_settings_and_hours(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $page = $this->bookWith(['contacts' => []]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('name="type_data[sort]"', false)
            ->assertSee('name="type_data[hide_numbers]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['sort' => 'alpha', 'hide_numbers' => '1', 'contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'hours' => 'pon.–śr. 10–12']]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $td = $page->refresh()->typeData();
        $this->assertSame('alpha', $td['sort']);
        $this->assertTrue((bool) $td['hide_numbers']);
        $this->assertSame('pon.–śr. 10–12', $td['contacts'][0]['hours']);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['sort' => 'losowo', 'contacts' => []],
        ])->assertSessionHasErrors('type_data.sort');
    }

    public function test_default_layout_is_a_table_per_department(): void
    {
        $this->bookWith(['contacts' => [
            ['group' => 'Biuro', 'name' => 'Anna Kowalska', 'role' => 'Koordynatorka', 'phone' => '601 350 487', 'topic' => 'Zapisy', 'hours' => 'pon.–śr.'],
            ['group' => 'Finanse', 'name' => 'Jan Nowak', 'phone' => '500 100 200', 'topic' => 'Faktury'],
        ]]);

        $html = $this->get('/ksiazka-telefoniczna')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, '<table class="phone-book-table'));
        $this->assertStringContainsString('<caption class="sr-only">Biuro: kto, numer telefonu, w jakiej sprawie', $html);
        $this->assertStringContainsString('<th scope="col">Numer komórkowy</th>', $html);
        $this->assertStringContainsString('<th scope="row" data-label="Osoba">', $html);
        $this->assertStringContainsString('data-label="W jakiej sprawie">Zapisy', $html);
        $this->assertStringNotContainsString('rounded-md bg-gray-50 p-5', $html);
    }

    public function test_cards_layout_and_missing_hours_column(): void
    {
        $this->bookWith(['layout' => 'cards', 'contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'topic' => 'Zapisy', 'hours' => 'pon.–śr.']]]);
        $html = $this->get('/ksiazka-telefoniczna')->assertOk()->getContent();
        $this->assertStringNotContainsString('<table class="phone-book-table', $html);
        $this->assertStringContainsString('Kiedy dzwonić:', $html);

        Page::where('slug', 'ksiazka-telefoniczna')->first()->update(['type_data' => ['contacts' => [['name' => 'Anna', 'phone' => '601 350 487', 'topic' => 'Zapisy']]]]);
        $this->get('/ksiazka-telefoniczna')->assertOk()->assertDontSee('<th scope="col">Kiedy dzwonić</th>', false);
    }

    public function test_admin_offers_layout_and_rejects_unknown_value(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);
        $page = $this->bookWith(['contacts' => []]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('name="type_data[layout]"', false);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['layout' => 'cards', 'contacts' => []],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cards', $page->refresh()->typeData()['layout']);

        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka telefoniczna', 'slug' => 'ksiazka-telefoniczna', 'type' => 'phone_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['layout' => 'siatka', 'contacts' => []],
        ])->assertSessionHasErrors('type_data.layout');
    }

    public function test_table_has_separate_email_column_only_when_someone_has_email(): void
    {
        $this->bookWith(['contacts' => [
            ['name' => 'Anna Kowalska', 'phone' => '601 350 487', 'email' => 'anna@example.org'],
            ['name' => 'Jan Nowak', 'phone' => '500 100 200'],
        ]]);

        $html = $this->get('/ksiazka-telefoniczna')->assertOk()->getContent();
        $this->assertStringContainsString('<th scope="col">E-mail</th>', $html);
        $this->assertStringContainsString('<td data-label="E-mail"><a href="mailto:anna@example.org"', $html);
        $this->assertStringContainsString('<td data-label="E-mail"></td>', $html);
        // E-mail nie jest już w kolumnie „Osoba”.
        $this->assertStringNotContainsString('font-normal"><i class="fa-solid fa-envelope"', $html);

        Page::where('slug', 'ksiazka-telefoniczna')->first()->update(['type_data' => ['contacts' => [['name' => 'Jan', 'phone' => '500 100 200']]]]);
        $this->get('/ksiazka-telefoniczna')->assertOk()->assertDontSee('<th scope="col">E-mail</th>', false);
    }
}
