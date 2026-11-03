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
}
