<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** „Zarządzaj subwitrynami”: lista z adresami i działaniami oraz zmiana nazwy i adresu (slug, domena) witryny. */
class SiteManagementTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCurrentSite();
        $this->main = SiteSetting::current();
        $this->sub = SiteSetting::create([
            'site_name' => 'Szkolenia', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test',
            'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function resetCurrentSite(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function actingAdmin(): static
    {
        return $this->actingAs($this->admin())->withSession(['admin_site_chosen' => true]);
    }

    public function test_strona_pokazuje_witryne_glowna_i_subwitryny_z_adresami_i_dzialaniami(): void
    {
        $this->sub->update(['under_construction' => true]);
        $open = SiteSetting::create([
            'site_name' => 'Ośrodek Północ', 'slug' => 'polnoc', 'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template, 'under_construction' => false,
        ]);
        $none = SiteSetting::create([
            'site_name' => 'Bez adresu', 'parent_site_id' => $this->main->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);

        $html = $this->actingAdmin()->get(route('admin.witryny.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Zarządzaj subwitrynami', $html);
        $this->assertStringContainsString('Witryna główna', $html);
        $this->assertStringContainsString('Subwitryny (3)', $html);
        // Adres: własna domena albo adres w serwisie; status słowami.
        $this->assertStringContainsString('szkolenia.example.test', $html);
        $this->assertStringContainsString('własna domena', $html);
        $this->assertStringContainsString(current_site_url($open), $html);
        $this->assertStringContainsString('adres w serwisie', $html);
        $this->assertStringContainsString('Dopiero tworzymy', $html);
        $this->assertStringContainsString('Publiczna', $html);
        $this->assertStringContainsString('Bez adresu', $html);
        // Działania przy każdej subwitrynie i zmiana nazwy witryny głównej.
        foreach ([$this->sub, $open, $none] as $site) {
            $this->assertStringContainsString(route('admin.witryny.przelacz', $site), $html);
            $this->assertStringContainsString(route('admin.witryny.nazwa', $site), $html);
            $this->assertStringContainsString(route('admin.witryny.edit', $site), $html);
            $this->assertStringContainsString(route('admin.witryny.confirm-destroy', $site), $html);
        }
        $this->assertStringContainsString(route('admin.witryny.nazwa', $this->main), $html);
        $this->assertStringContainsString('Zmień nazwę i adres', $html);
        $this->assertStringContainsString('Nowa subwitryna', $html);
        // Dostępność: tabela z opisem i nagłówkami kolumn.
        $this->assertStringContainsString('<caption class="sr-only">', $html);
        $this->assertStringContainsString('<th scope="col"', $html);
    }

    public function test_zagniezdzona_subwitryna_jest_na_liscie_z_nazwa_rodzica(): void
    {
        SiteSetting::create([
            'site_name' => 'Klub Młodych', 'slug' => 'klub', 'parent_site_id' => $this->sub->id, 'site_kind' => 'standard',
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);

        $this->actingAdmin()->get(route('admin.witryny.index'))->assertOk()
            ->assertSee('Subwitryny (2)')
            ->assertSee('Klub Młodych')
            ->assertSee('w: Szkolenia');
    }

    public function test_pusta_lista_zacheca_do_zalozenia_subwitryny(): void
    {
        $this->sub->forceDelete();
        $this->resetCurrentSite();

        $this->actingAdmin()->get(route('admin.witryny.index'))->assertOk()
            ->assertSee('Nie ma jeszcze subwitryn')
            ->assertSee(route('admin.witryny.create'), false);
    }

    public function test_formularz_pokazuje_biezace_wartosci_i_podglad_adresu(): void
    {
        $html = $this->actingAdmin()->get(route('admin.witryny.nazwa', $this->sub))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Zmień nazwę i adres witryny', $html);
        $this->assertStringContainsString('value="Szkolenia"', $html);
        $this->assertStringContainsString('name="slug"', $html);
        $this->assertStringContainsString('value="szkolenia"', $html);
        $this->assertStringContainsString('name="domain"', $html);
        $this->assertStringContainsString('value="szkolenia.example.test"', $html);
        $this->assertStringContainsString('Adres po zapisaniu', $html);
        $this->assertStringContainsString('Obecnie: https://szkolenia.example.test', $html);
        $this->assertStringContainsString('dotychczasowy adres przestanie działać', $html);
        // Etykiety powiązane z polami (WCAG): for/id.
        foreach (['site_name', 'slug', 'domain'] as $id) {
            $this->assertStringContainsString('<label for="'.$id.'"', $html);
        }
    }

    public function test_witryna_glowna_ma_w_formularzu_tylko_nazwe(): void
    {
        $html = $this->actingAdmin()->get(route('admin.witryny.nazwa', $this->main))->assertOk()->getContent();

        $this->assertStringContainsString('name="site_name"', $html);
        $this->assertStringNotContainsString('name="slug"', $html);
        $this->assertStringNotContainsString('name="domain"', $html);
        $this->assertStringContainsString('Adres witryny głównej to adres serwera', $html);

        $this->put(route('admin.witryny.nazwa.update', $this->main), ['site_name' => 'Fundacja Nowa Nazwa', 'slug' => 'inny', 'domain' => 'inna.example.test'])
            ->assertRedirect(route('admin.witryny.index'))->assertSessionHasNoErrors();

        $main = $this->main->fresh();
        $this->assertSame('Fundacja Nowa Nazwa', $main->site_name);
        $this->assertNull($main->slug, 'Adresu witryny głównej nie zmienia się tutaj.');
        $this->assertNull($main->domain);
    }

    public function test_zmiana_nazwy_i_adresu_zapisuje_sie_i_nowy_adres_dziala(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'Centrum Szkoleń', 'slug' => 'centrum', 'domain' => ''])
            ->assertRedirect(route('admin.witryny.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'Centrum Szkoleń') && str_contains($status, rtrim(config('app.url'), '/').'/centrum'));

        $site = $this->sub->fresh();
        $this->assertSame(['Centrum Szkoleń', 'centrum', null], [$site->site_name, $site->slug, $site->domain]);
        $this->assertSame(rtrim(config('app.url'), '/').'/centrum', current_site_url($site));

        // Nowy adres rozpoznaje middleware (mapa slugów jest czyszczona przy zapisie), stary już nie.
        $this->resetCurrentSite();
        $this->get('/centrum')->assertOk();
        $this->resetCurrentSite();
        $this->get('/szkolenia')->assertNotFound();
    }

    public function test_adres_wpisany_po_ludzku_jest_normalizowany(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.nazwa.update', $this->sub), [
            'site_name' => 'Szkolenia', 'slug' => '  Nowy-Adres ', 'domain' => 'https://Szkolenia.PRZYKLAD.pl/kontakt?x=1',
        ])->assertSessionHasNoErrors();

        $site = $this->sub->fresh();
        $this->assertSame('nowy-adres', $site->slug);
        $this->assertSame('szkolenia.przyklad.pl', $site->domain);
    }

    public function test_usuniecie_domeny_i_adresu_zostawia_witryne_bez_adresu(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'Szkolenia', 'slug' => '', 'domain' => ''])
            ->assertSessionHasNoErrors();

        $site = $this->sub->fresh();
        $this->assertNull($site->slug);
        $this->assertNull($site->domain);
    }

    /** @return array<string, array{0: array<string, string>, 1: string, 2: ?string}> */
    public static function nieprawidloweAdresy(): array
    {
        return [
            'adres zarezerwowany' => [['slug' => 'admin'], 'slug', 'Ten adres jest zarezerwowany dla witryny głównej albo panelu — wybierz inny.'],
            'adres zajęty przez witrynę' => [['slug' => 'inna'], 'slug', 'Ten adres jest już zajęty przez inną witrynę albo stronę.'],
            'adres zajęty przez stronę' => [['slug' => 'zajeta-strona'], 'slug', null],
            'adres ze spacją i polskimi znakami' => [['slug' => 'złe znaki'], 'slug', null],
            'domena zajęta' => [['domain' => 'inna.example.test'], 'domain', 'Ta domena jest już przypisana do innej witryny.'],
            'domena ze spacjami' => [['domain' => 'to nie jest domena'], 'domain', null],
            'domena bez kropki' => [['domain' => 'bezkropki'], 'domain', null],
            'domena witryny głównej' => [['domain' => 'FEER.example.test'], 'domain', 'Ta domena to adres witryny głównej — wpisz inną dla subwitryny.'],
            'pusta nazwa' => [['site_name' => ''], 'site_name', null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nieprawidloweAdresy')]
    public function test_walidacja_adresu_odrzuca_zajete_zarezerwowane_i_niepoprawne(array $data, string $field, ?string $message): void
    {
        $other = SiteSetting::create([
            'site_name' => 'Inna', 'slug' => 'inna', 'domain' => 'inna.example.test', 'parent_site_id' => $this->main->id,
            'site_kind' => 'standard', 'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
        Page::create(['site_id' => $this->main->id, 'title' => 'Zajęta', 'slug' => 'zajeta-strona', 'type' => 'standard', 'is_published' => true]);
        config(['app.url' => 'https://feer.example.test']);

        $response = $this->actingAdmin()->from(route('admin.witryny.nazwa', $this->sub))
            ->put(route('admin.witryny.nazwa.update', $this->sub), $data + ['site_name' => 'Szkolenia'])
            ->assertRedirect(route('admin.witryny.nazwa', $this->sub));
        $message === null ? $response->assertSessionHasErrors($field) : $response->assertSessionHasErrors([$field => $message]);

        // Nic się nie zapisało.
        $site = $this->sub->fresh();
        $this->assertSame(['Szkolenia', 'szkolenia', 'szkolenia.example.test'], [$site->site_name, $site->slug, $site->domain]);
        $this->assertSame('inna', $other->fresh()->slug);
    }

    public function test_witryna_moze_zachowac_wlasny_adres_i_domene_przy_zmianie_samej_nazwy(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Szkolenia i warsztaty', $this->sub->fresh()->site_name);
    }

    public function test_zmiana_jest_zapisana_w_dzienniku_zdarzen(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'Szkolenia 2', 'slug' => 'szkolenia', 'domain' => 'szkolenia.example.test'])
            ->assertSessionHasNoErrors();

        $entry = Activity::query()->where('description', 'like', 'Zmieniono nazwę lub adres witryny%')->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame('Szkolenia', $entry->properties['before']['site_name']);
        $this->assertSame('Szkolenia 2', $entry->properties['after']['site_name']);
    }

    public function test_wymaga_administratora_sieci_i_nie_dotyczy_witryn_w_koszu(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->get(route('admin.witryny.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.witryny.nazwa', $this->sub))->assertForbidden();
        $this->actingAs($editor)->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'X'])->assertForbidden();
        $this->assertSame('Szkolenia', $this->sub->fresh()->site_name);

        $this->sub->forceFill(['trashed_at' => now()])->save();
        $this->actingAdmin()->get(route('admin.witryny.nazwa', $this->sub))->assertNotFound();
        $this->put(route('admin.witryny.nazwa.update', $this->sub), ['site_name' => 'X'])->assertNotFound();
    }

    public function test_pasek_boczny_i_menu_prowadza_do_zarzadzania_subwitrynami(): void
    {
        $admin = $this->admin();
        $html = $this->actingAs($admin)->withSession(['admin_site_chosen' => true])->get(route('admin.dashboard'))->assertOk()->getContent();

        // Wyskakujące menu wyboru witryny: nowa nazwa linku i zmiana nazwy bieżącej witryny.
        $this->assertStringContainsString('>Zarządzaj subwitrynami</a>', $html);
        $this->assertStringNotContainsString('Zarządzaj witrynami', $html);
        $this->assertStringContainsString(route('admin.witryny.nazwa', $this->main), $html);
        $this->assertStringContainsString('Zmień nazwę i adres<span class="sr-only"> tej witryny</span>', $html);

        $labels = collect(AdminMenu::for($admin))->flatMap(fn ($g) => $g['blocks'])->flatMap(fn ($b) => $b['items'])->pluck('label', 'key');
        $this->assertSame('Zarządzaj subwitrynami', $labels['sites']);
    }
}
