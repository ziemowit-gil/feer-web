<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PhoneBookEntry;
use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Contact\Support\ContactSections;
use Modules\Contact\Support\LegacyPagesConverter;
use Tests\TestCase;

/**
 * Moduł Kontakt (modules/Contact): typy stron „Kontakt” i „Kontakt — książka”, automatyczne podstrony /kontakt/…
 * i przenoszenie dawnych stron „Książka telefoniczna” na nowy typ.
 */
class ContactModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSettings();
    }

    private function resetSettings(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function feerContact(array $extra = []): void
    {
        SiteSetting::current()->update($extra + ['site_template' => 'feer', 'contact_layout' => 'tabs']);
        $this->resetSettings();
    }

    private function person(string $name, array $extra = []): PhoneBookEntry
    {
        return PhoneBookEntry::create($extra + ['name' => $name, 'phone' => '601 350 487', 'group' => 'Biuro', 'is_active' => true]);
    }

    /** @return array{0: Page, 1: Page} strona kontaktu i jej podstrona „Kontakt — książka” */
    private function contactWithBook(): array
    {
        $contact = Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);
        $book = Page::create(['title' => 'Książka telefoniczna', 'slug' => 'ksiazka', 'type' => 'contact_book', 'parent_id' => $contact->id, 'is_published' => true]);

        return [$contact, $book];
    }

    // ── Moduł i typy ─────────────────────────────────────────────────────────

    public function test_modul_jest_wykryty_i_aktywny_a_typy_stron_zarejestrowane(): void
    {
        $manager = app(ModuleManager::class);
        $this->assertNotNull($manager->get('contact'));
        $this->assertTrue($manager->isActive('contact'));

        $types = Page::types();
        $this->assertSame('Kontakt — książka (kto, numer komórkowy i w jakiej sprawie dzwonić)', $types['contact_book']);
        $this->assertStringStartsWith('Kontakt (dane, formularz i podstrony', $types['contact']);

        $group = Page::typeGroups()['Kontakt i wydarzenia'];
        $this->assertContains('contact', $group);
        $this->assertContains('contact_book', $group);
    }

    public function test_dawny_typ_ksiazki_jest_wycofany_z_wyboru_ale_dziala_dla_istniejacych_stron(): void
    {
        $this->assertNotContains('phone_book', Page::typeGroups()['Kontakt i wydarzenia']);
        $this->assertArrayHasKey('phone_book', Page::types()); // etykieta zostaje, żeby stare strony miały nazwę typu

        $legacy = Page::create(['title' => 'Stara książka', 'slug' => 'stara-ksiazka', 'type' => 'phone_book', 'is_published' => true,
            'type_data' => ['contacts' => [['name' => 'Jan Nowak', 'phone' => '500 100 200', 'group' => 'Biuro']]]]);

        // Do czasu przeniesienia strona wyświetla się jak dotąd (osoby z pól strony).
        $this->get('/'.$legacy->slug)->assertOk()->assertSee('Jan Nowak');

        // Formularz edycji zachowuje dawny typ w polu wyboru (inaczej zapis zmieniłby typ strony).
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $html = $this->actingAs($admin)->get(route('admin.podstrony.edit', $legacy))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~<option value="phone_book"\s+selected~', $html);
    }

    // ── Typ „Kontakt — książka” ───────────────────────────────────────────────

    public function test_strona_ksiazki_pokazuje_osoby_z_tabeli_z_ustawieniami_wygladu(): void
    {
        $this->person('Anna Kowalska', ['group' => 'Zarząd', 'role' => 'Prezeska', 'topic' => 'Sprawy organizacji', 'hours' => 'wt–czw 12–16']);
        $this->person('Piotr Zieliński', ['group' => 'Koordynatorzy', 'phone' => '602 200 300']);
        $page = Page::create(['title' => 'Książka telefoniczna', 'slug' => 'ksiazka', 'type' => 'contact_book', 'is_published' => true,
            'type_data' => ['layout' => 'cards', 'hide_numbers' => '1']]);

        $html = $this->get('/ksiazka')->assertOk()->assertSee('Anna Kowalska')->assertSee('Piotr Zieliński')->assertSee('Sprawy organizacji')
            ->assertSee('data-page-type="contact_book"', false)->getContent();
        // Ukrywanie numerów: przycisk zamiast numeru (ten sam mechanizm co w module).
        $this->assertStringContainsString('phone-reveal', $html);
        $this->assertNotNull($page->fresh());
    }

    public function test_strona_ksiazki_moze_pokazywac_tylko_jeden_dzial(): void
    {
        $this->person('Anna Kowalska', ['group' => 'Zarząd']);
        $this->person('Piotr Zieliński', ['group' => 'Koordynatorzy', 'phone' => '602 200 300']);
        Page::create(['title' => 'Zarząd', 'slug' => 'zarzad-tel', 'type' => 'contact_book', 'is_published' => true, 'type_data' => ['group' => 'zarząd']]);

        $this->get('/zarzad-tel')->assertOk()->assertSee('Anna Kowalska')->assertDontSee('Piotr Zieliński');
    }

    public function test_strona_ksiazki_bez_osob_w_tabeli_pokazuje_stare_dane_ze_strony(): void
    {
        Page::create(['title' => 'Książka', 'slug' => 'ksiazka', 'type' => 'contact_book', 'is_published' => true,
            'type_data' => ['contacts' => [['name' => 'Ewa Stara', 'phone' => '500 100 200']]]]);

        $this->get('/ksiazka')->assertOk()->assertSee('Ewa Stara');
    }

    public function test_formularz_strony_ma_pola_ksiazki_i_podstrona_dostaje_rodzica(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$contact] = $this->contactWithBook();

        $html = $this->actingAs($admin)->get(route('admin.podstrony.create', ['type' => 'contact_book', 'parent_id' => $contact->id]))->assertOk()->getContent();
        $this->assertStringContainsString('data-type-fields="contact_book"', $html);
        $this->assertStringContainsString('name="type_data[group]"', $html);
        $this->assertMatchesRegularExpression('~<option value="'.$contact->id.'"[^>]*selected~', $html);
    }

    public function test_edycja_strony_kontaktu_pokazuje_karte_podstron_ze_skrotem_do_ksiazki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $contact = Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'contact', 'is_published' => true]);
        $this->feerContact(['bank_account_number' => '09 1020 2906', 'contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']]]);

        $html = $this->actingAs($admin)->get(route('admin.podstrony.edit', $contact))->assertOk()->getContent();
        $this->assertStringContainsString('Podstrony kontaktu', $html);
        $this->assertStringContainsString('Dodaj książkę telefoniczną jako podstronę', $html);
        $this->assertStringContainsString('type=contact_book', $html);

        // Po dodaniu książki skrót znika.
        Page::create(['title' => 'Książka', 'slug' => 'ksiazka', 'type' => 'contact_book', 'parent_id' => $contact->id, 'is_published' => true]);
        $this->actingAs($admin)->get(route('admin.podstrony.edit', $contact))->assertOk()->assertDontSee('Dodaj książkę telefoniczną jako podstronę');
    }

    // ── Menu boczne i podstrony kontaktu ──────────────────────────────────────

    public function test_ksiazka_bedaca_podstrona_kontaktu_jest_w_menu_boczym_raz(): void
    {
        $this->feerContact(['krs_number' => '0000779281']);
        [, $book] = $this->contactWithBook();
        $this->person('Anna Kowalska');

        $html = $this->get('/kontakt-2')->assertOk()->getContent();
        $nav = substr($html, strpos($html, 'aria-label="Menu strony kontaktu"'));
        $nav = substr($nav, 0, strpos($nav, '</nav>'));

        $this->assertSame(1, substr_count($nav, 'href="'.$book->publicUrl().'"'), 'książka raz, jako podstrona kontaktu');
        $this->assertStringContainsString('Książka telefoniczna', $nav);
    }

    public function test_link_do_ksiazki_wskazuje_strone_kontakt_ksiazka_przed_modulem(): void
    {
        $this->person('Anna Kowalska'); // moduł ma wpisy
        [, $book] = $this->contactWithBook();

        $found = ContactSections::phoneBook(SiteSetting::current());
        $this->assertSame($book->id, $found['page_id']);
        $this->assertSame($book->publicUrl(), $found['url']);
    }

    public function test_podstrony_kontaktu_dzialaja_przy_aktywnym_module_i_znikaja_po_jego_wylaczeniu(): void
    {
        $this->feerContact(['contact_bank_accounts' => [['number' => 'PL12 3456', 'purpose' => 'Darowizny']]]);

        $this->get('/kontakt/rachunki')->assertOk()->assertSee('PL12 3456');
        $this->get('/kontakt')->assertOk()->assertSee('aria-label="Menu strony kontaktu"', false);

        app(ModuleManager::class)->deactivate('contact');
        $this->get('/kontakt/rachunki')->assertNotFound();
        $this->get('/kontakt')->assertOk()->assertDontSee('aria-label="Menu strony kontaktu"', false);
    }

    // ── Przenoszenie dawnych stron ────────────────────────────────────────────

    private function legacyBookPage(array $contacts, array $extra = []): Page
    {
        return Page::create($extra + ['title' => 'Książka', 'slug' => 'ksiazka-stara', 'type' => 'phone_book', 'is_published' => true,
            'type_data' => ['layout' => 'cards', 'sort' => 'alpha', 'contacts' => $contacts]]);
    }

    public function test_konwerter_przenosi_osoby_do_tabeli_zmienia_typ_i_zapisuje_kopie(): void
    {
        $this->person('Anna Kowalska', ['group' => 'Biuro', 'phone' => '601 350 487']); // już jest w tabeli
        $page = $this->legacyBookPage([
            ['name' => 'Anna Kowalska', 'phone' => '601-350-487', 'group' => 'Biuro'],   // duplikat (inny zapis numeru)
            ['name' => 'Jan Nowak', 'phone' => '500 100 200', 'group' => 'Zarząd', 'role' => 'Wiceprezes', 'topic' => 'Media', 'hours' => 'pon 10–12', 'email' => 'jan@example.org'],
            ['name' => '', 'phone' => '1'],                                                 // bez nazwiska — pomijane
        ]);

        $converter = app(LegacyPagesConverter::class);
        $dry = $converter->convert(true);
        $this->assertSame(['pages' => 1, 'created' => 1, 'existing' => 1, 'backup' => null, 'dry_run' => true, 'note' => null], $dry);
        $this->assertSame('phone_book', $page->fresh()->type, 'próba nic nie zmienia');
        $this->assertSame(1, PhoneBookEntry::count());

        $result = $converter->convert();

        $this->assertSame(1, $result['pages']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['existing']);
        $this->assertSame('contact_book', $page->fresh()->type);
        $this->assertSame(2, PhoneBookEntry::count());
        $jan = PhoneBookEntry::where('name', 'Jan Nowak')->first();
        $this->assertSame(['Zarząd', 'Wiceprezes', '500 100 200', 'jan@example.org', 'Media', 'pon 10–12'], [$jan->group, $jan->role, $jan->phone, $jan->email, $jan->topic, $jan->hours]);
        $this->assertSame($page->site_id, $jan->site_id);
        // Ustawienia wyglądu i stare osoby zostają w stronie (można wrócić do poprzedniego typu).
        $td = $page->fresh()->type_data;
        $this->assertSame(['cards', 'alpha'], [$td['layout'], $td['sort']]);
        $this->assertCount(3, $td['contacts']);

        $this->assertFileExists($result['backup']);
        $backup = json_decode(file_get_contents($result['backup']), true);
        $this->assertSame([$page->id], array_column($backup['pages'], 'id'));
        $this->assertSame('phone_book', $backup['pages'][0]['old_type']);

        // Idempotentne: drugi przebieg nie ma czego przenosić.
        $again = $converter->convert();
        $this->assertSame(0, $again['pages']);
        $this->assertSame(2, PhoneBookEntry::count());

        File::delete($result['backup']);
    }

    public function test_cofniecie_przywraca_dawny_typ_stron_z_kopii(): void
    {
        $page = $this->legacyBookPage([['name' => 'Jan Nowak', 'phone' => '500 100 200']]);
        $converter = app(LegacyPagesConverter::class);
        $result = $converter->convert();
        $this->assertSame('contact_book', $page->fresh()->type);

        $this->assertSame(1, $converter->restore($result['backup']));
        $this->assertSame('phone_book', $page->fresh()->type);
        $this->assertSame(0, $converter->restore($result['backup']), 'drugie cofnięcie nie ma już czego przywracać');

        File::delete($result['backup']);
    }

    public function test_polecenie_artisan_pokazuje_wynik_proby_i_przenosi_strony(): void
    {
        $page = $this->legacyBookPage([['name' => 'Jan Nowak', 'phone' => '500 100 200']]);

        $this->artisan('kontakt:przenies-strony', ['--sprawdz' => true])
            ->expectsOutputToContain('[próba] Strony do przeniesienia: 1; osoby dodane do książki: 1')
            ->assertSuccessful();
        $this->assertSame('phone_book', $page->fresh()->type);

        $this->artisan('kontakt:przenies-strony')->expectsOutputToContain('Kopia stron:')->assertSuccessful();
        $this->assertSame('contact_book', $page->fresh()->type);

        File::delete(app(LegacyPagesConverter::class)->latestBackup());
    }

    public function test_zmiana_typu_w_edytorze_nie_gubi_osob_ze_strony(): void
    {
        $page = $this->legacyBookPage([['name' => 'Jan Nowak', 'phone' => '500 100 200', 'group' => 'Biuro']]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Formularz typu „Kontakt — książka” nie wysyła osób (są w module) — dawniej przepadały.
        $this->actingAs($admin)->put(route('admin.podstrony.update', $page), [
            'title' => 'Książka', 'slug' => 'ksiazka-stara', 'type' => 'contact_book', 'parent_id' => '', 'is_published' => '1',
            'type_data' => ['layout' => 'table'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('contact_book', $page->fresh()->type);
        $this->assertSame(1, PhoneBookEntry::where('name', 'Jan Nowak')->count());
        $this->assertCount(1, $page->fresh()->type_data['contacts']);
        auth()->logout();
        $this->get('/ksiazka-stara')->assertOk()->assertSee('Jan Nowak');
    }

    public function test_dawna_strona_bez_wlasnych_osob_pokazuje_osoby_z_modulu(): void
    {
        $this->person('Anna Kowalska');
        Page::create(['title' => 'Nasze numery telefonów', 'slug' => 'numery', 'type' => 'phone_book', 'is_published' => true, 'type_data' => ['contacts' => []]]);

        $this->get('/numery')->assertOk()->assertSee('Anna Kowalska');
    }

    public function test_przeniesiona_strona_wyswietla_osoby_z_tabeli(): void
    {
        $this->legacyBookPage([['name' => 'Jan Nowak', 'phone' => '500 100 200', 'group' => 'Zarząd']]);
        $result = app(LegacyPagesConverter::class)->convert();

        $this->get('/ksiazka-stara')->assertOk()->assertSee('Jan Nowak')->assertSee('data-page-type="contact_book"', false);

        File::delete($result['backup']);
    }
}
