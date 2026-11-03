<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\ResolveCurrentSite;
use App\Models\Category;
use App\Models\Page;
use App\Models\Redirect;
use App\Models\SiteAddressRedirect;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteRemoval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Po zmianie adresu skróconego albo domeny subwitryny dawny adres przekierowuje (301) na obecny, ze ścieżką i zapytaniem
 * (SiteAddressRedirect + ResolveCurrentSite); lista dawnych adresów jest na stronie „Zmień nazwę i adres”.
 */
class SiteAddressRedirectTest extends TestCase
{
    use RefreshDatabase;

    private SiteSetting $main;

    private SiteSetting $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCurrentSite();
        $this->main = SiteSetting::current();
        $this->sub = $this->makeSite('Szkolenia', 'szkolenia', 'szkolenia.example.test');
    }

    private function makeSite(string $name, ?string $slug, ?string $domain = null, string $kind = 'standard'): SiteSetting
    {
        return SiteSetting::create([
            'site_name' => $name, 'slug' => $slug, 'domain' => $domain, 'parent_site_id' => $this->main->id, 'site_kind' => $kind,
            'brand_color' => SiteSetting::DEFAULT_BRAND_COLOR, 'site_template' => $this->main->site_template,
        ]);
    }

    private function resetCurrentSite(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        app()->forgetInstance(SiteSetting::CURRENT_SITE_BINDING);
    }

    private function actingAdmin(): static
    {
        return $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))->withSession(['admin_site_chosen' => true]);
    }

    private function root(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Żądanie publiczne w „czystym” kontekście witryny (jak osobne żądanie). Adres zawsze pełny: generator adresów
     * pamięta host poprzedniego żądania (np. starej domeny), więc względna ścieżka trafiłaby na ten sam host.
     */
    private function visit(string $uri): TestResponse
    {
        $this->resetCurrentSite();

        return $this->get(str_starts_with($uri, 'http') ? $uri : $this->root().$uri);
    }

    /** Zmiana adresu w panelu („Zmień nazwę i adres”); nazwa zostaje, chyba że podano inną. */
    private function rename(array $address, ?SiteSetting $site = null): TestResponse
    {
        $site ??= $this->sub;

        $this->resetCurrentSite();

        return $this->actingAdmin()->put($this->root().route('admin.witryny.nazwa.update', $site, false), $address + ['site_name' => $site->site_name]);
    }

    /** @return list<string> */
    private function redirectsOf(SiteSetting $site): array
    {
        return SiteAddressRedirect::query()->where('target_site_id', $site->id)->get()
            ->map(fn ($redirect) => $redirect->type.':'.$redirect->value)->sort()->values()->all();
    }

    public function test_zmiana_adresu_skroconego_przekierowuje_stary_adres_301_ze_sciezka_i_zapytaniem(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przekierowuje teraz na nowy') && str_contains($status, '/szkolenia'));

        // Dawny adres skrócony i dawna domena (bez adresu własnej domeny witryna jest pod adresem w serwisie).
        $this->assertSame(['domain:szkolenia.example.test', 'slug:szkolenia'], $this->redirectsOf($this->sub));

        $this->visit('/szkolenia/aktualnosci?strona=2')->assertStatus(301)->assertRedirect($this->root().'/centrum/aktualnosci?strona=2');
        $this->visit('/szkolenia')->assertStatus(301)->assertRedirect($this->root().'/centrum');
        // Wielkość liter w adresie skróconym nie ma znaczenia, ścieżka zostaje bez zmian.
        $this->visit('/SZKOLENIA/O-Nas')->assertStatus(301)->assertRedirect($this->root().'/centrum/O-Nas');

        // Nowy adres działa normalnie.
        $this->visit('/centrum')->assertOk();
    }

    public function test_ukosnik_na_koncu_sciezki_jest_zachowany(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $middleware = app(\App\Http\Middleware\ResolveCurrentSite::class);
        $next = fn () => response('dalej');

        // Klient testowy obcina ukośnik na końcu adresu, więc wołamy middleware z żądaniem zbudowanym ręcznie.
        foreach (['/szkolenia/' => '/centrum/', '/szkolenia/a/b/' => '/centrum/a/b/', '/szkolenia' => '/centrum'] as $from => $to) {
            $this->resetCurrentSite();
            $response = $middleware->handle(Request::create($this->root().$from), $next);

            $this->assertSame(301, $response->getStatusCode(), $from);
            $this->assertSame($this->root().$to, $response->headers->get('Location'), $from);
        }
    }

    public function test_stara_domena_przekierowuje_na_nowa_domene_ze_sciezka_i_zapytaniem(): void
    {
        $this->rename(['slug' => 'szkolenia', 'domain' => 'kursy.example.test'])->assertSessionHasNoErrors();

        $this->assertSame(['domain:szkolenia.example.test'], $this->redirectsOf($this->sub));

        $this->visit('http://szkolenia.example.test/aktualnosci?x=1')->assertStatus(301)->assertRedirect('https://kursy.example.test/aktualnosci?x=1');
        $this->visit('http://szkolenia.example.test')->assertStatus(301)->assertRedirect('https://kursy.example.test/');
        $this->visit('http://kursy.example.test/')->assertOk();
    }

    public function test_usunieta_domena_przekierowuje_na_adres_w_serwisie(): void
    {
        $this->rename(['slug' => 'szkolenia', 'domain' => ''])->assertSessionHasNoErrors();

        $this->visit('http://szkolenia.example.test/kontakt?a=b')->assertStatus(301)->assertRedirect($this->root().'/szkolenia/kontakt?a=b');
    }

    public function test_witryna_bez_adresu_nie_przekierowuje_a_po_nadaniu_nowego_zaczyna(): void
    {
        $this->rename(['slug' => '', 'domain' => ''])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'zacznie przekierowywać'));

        $this->visit('/szkolenia/kontakt')->assertNotFound();
        $this->assertFalse($this->visit('http://szkolenia.example.test/')->isRedirect(), 'Nie ma dokąd przekierować.');

        $this->rename(['slug' => 'nowy', 'domain' => ''])->assertSessionHasNoErrors();

        $this->visit('/szkolenia/kontakt')->assertStatus(301)->assertRedirect($this->root().'/nowy/kontakt');
    }

    public function test_witryna_w_koszu_nie_przekierowuje(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->visit('/szkolenia/x')->assertStatus(301);

        SiteRemoval::trash($this->sub->fresh());
        $this->visit('/szkolenia/x')->assertNotFound();

        SiteRemoval::restore($this->sub->fresh());
        $this->visit('/szkolenia/x')->assertStatus(301)->assertRedirect($this->root().'/centrum/x');
    }

    public function test_powrot_do_dawnego_adresu_konczy_jego_przekierowanie(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->rename(['slug' => 'szkolenia', 'domain' => ''])->assertSessionHasNoErrors();

        $slugs = SiteAddressRedirect::query()->where('type', 'slug')->pluck('value')->all();
        $this->assertSame(['centrum'], $slugs, 'Żywy adres nie jest przekierowaniem, a ten, z którego wróciliśmy, jest.');

        $this->visit('/szkolenia')->assertOk();
        $this->visit('/centrum/x')->assertStatus(301)->assertRedirect($this->root().'/szkolenia/x');
    }

    public function test_kolejne_zmiany_nie_tworza_lancuchow_a_mapa_jest_odswiezana(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->visit('/szkolenia')->assertStatus(301); // rozgrzewa mapę w pamięci podręcznej

        $this->rename(['slug' => 'ostateczny', 'domain' => ''])->assertSessionHasNoErrors();

        $this->visit('/centrum/x')->assertStatus(301)->assertRedirect($this->root().'/ostateczny/x');
        $this->visit('/szkolenia/x')->assertStatus(301)->assertRedirect($this->root().'/ostateczny/x');
    }

    public function test_zajecie_dawnego_adresu_przez_inna_witryne_konczy_przekierowanie_i_panel_o_tym_mowi(): void
    {
        $other = $this->makeSite('Inna', 'inna');
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->assertContains('slug:szkolenia', $this->redirectsOf($this->sub));

        $this->rename(['slug' => 'szkolenia', 'domain' => ''], $other)->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przestał przekierowywać na witrynę „Szkolenia”'));

        $this->assertNotContains('slug:szkolenia', $this->redirectsOf($this->sub));
        $response = $this->visit('/szkolenia');
        $this->assertFalse($response->isRedirect(), 'Żywy adres wygrywa z przekierowaniem.');
        $response->assertOk();
    }

    public function test_nowa_witryna_na_dawnym_adresie_tez_konczy_przekierowanie(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

        $this->actingAdmin()->post(route('admin.witryny.store'), ['site_name' => 'Nowa', 'slug' => 'szkolenia', 'domain' => ''])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'została założona') && str_contains($status, 'przestał przekierowywać'));

        $this->assertNotContains('slug:szkolenia', $this->redirectsOf($this->sub));
        $this->assertFalse($this->visit('/szkolenia')->isRedirect());
    }

    public function test_istniejaca_strona_wygrywa_z_przekierowaniem(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        Page::create(['site_id' => $this->main->id, 'title' => 'Szkolenia', 'slug' => 'szkolenia', 'type' => 'standard', 'is_published' => true]);

        $response = $this->visit('/szkolenia');

        $this->assertFalse($response->isRedirect(), 'Strona o tym adresie jest ważniejsza niż przekierowanie.');
        $response->assertOk();
    }

    public function test_adres_zajety_na_stale_przez_trase_nie_staje_sie_przekierowaniem(): void
    {
        // BIP może mieć adres /bip (zwykle zajęty przez trasę witryny głównej); po zmianie /bip wraca do swojej trasy.
        $bip = $this->makeSite('BIP', 'bip', null, 'bip');

        $bip->update(['slug' => 'bip-fundacji']);

        $this->assertNull(SiteAddressRedirect::siteIdFor('slug', 'bip'));
        $this->assertSame([], $this->redirectsOf($bip));
    }

    public function test_przekierowanie_zachowuje_metode_zapytan_innych_niz_get(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->resetCurrentSite();

        $this->post('/szkolenia/zapis?x=1', ['a' => 'b'])->assertStatus(308)->assertRedirect($this->root().'/centrum/zapis?x=1');
        $this->resetCurrentSite();
        $this->call('HEAD', '/szkolenia/x')->assertStatus(301);
    }

    public function test_dawny_adres_site_slug_przekierowuje_od_razu_na_obecny(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

        $this->visit('/site/szkolenia/kontakt?a=1')->assertStatus(301)->assertRedirect($this->root().'/centrum/kontakt?a=1');
    }

    public function test_sciezka_nie_pozwala_zmienic_hosta_przekierowania(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => 'kursy.example.test'])->assertSessionHasNoErrors();

        $bySlug = $this->visit('/szkolenia//evil.example.test/x')->assertStatus(301);
        $byDomain = $this->visit('http://szkolenia.example.test//evil.example.test/x')->assertStatus(301);

        foreach ([$bySlug, $byDomain] as $response) {
            $this->assertSame('kursy.example.test', parse_url((string) $response->headers->get('Location'), PHP_URL_HOST));
        }
    }

    public function test_pelna_edycja_tez_zapamietuje_dawny_adres(): void
    {
        $this->actingAdmin()->put(route('admin.witryny.update', $this->sub), ['site_name' => 'Szkolenia', 'slug' => 'pelna-edycja', 'domain' => 'szkolenia.example.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przekierowuje teraz na nowy'));

        $this->assertSame(['slug:szkolenia'], $this->redirectsOf($this->sub));
        // Witryna ma domenę, więc dawny adres skrócony prowadzi na nią.
        $this->visit('/szkolenia/x')->assertStatus(301)->assertRedirect('https://szkolenia.example.test/x');
    }

    public function test_zmiana_samej_nazwy_i_zmiany_witryny_glownej_nie_tworza_przekierowan(): void
    {
        $this->rename(['slug' => 'szkolenia', 'domain' => 'szkolenia.example.test', 'site_name' => 'Szkolenia i warsztaty'])->assertSessionHasNoErrors();
        $this->sub->fresh()->update(['under_construction' => true]);
        $this->main->update(['domain' => 'glowna.example.test']);
        $this->main->fresh()->update(['domain' => 'inna-glowna.example.test']);

        $this->assertSame(0, SiteAddressRedirect::query()->count());
    }

    public function test_formularz_pokazuje_dawne_adresy_i_pozwala_je_usunac(): void
    {
        $empty = $this->actingAdmin()->get(route('admin.witryny.nazwa', $this->sub))->assertOk()->getContent();
        $this->assertStringContainsString('Przekierowania ze starych adresów', $empty);
        $this->assertStringContainsString('Nie ma jeszcze żadnych', $empty);

        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $redirect = SiteAddressRedirect::query()->where('type', 'slug')->firstOrFail();

        $html = $this->get(route('admin.witryny.nazwa', $this->sub))->assertOk()->getContent();
        $this->assertStringContainsString(preg_replace('#^https?://#', '', $this->root()).'/szkolenia', $html);
        $this->assertStringContainsString('szkolenia.example.test', $html);
        // Data z wiersza, nie z zegara: test nie może zależeć od tego, czy przebieg przecina północ.
        $this->assertStringContainsString('Adres skrócony · od '.$redirect->created_at->format('d.m.Y'), $html);
        $this->assertStringContainsString('Usuń przekierowanie<span class="sr-only"> z adresu', $html);

        // Formularz usuwania naprawdę działa w przeglądarce: POST z _method=DELETE i tokenem CSRF, z potwierdzeniem.
        $action = preg_quote(route('admin.witryny.przekierowania.destroy', [$this->sub, $redirect]), '#');
        $this->assertMatchesRegularExpression('#<form method="POST" action="'.$action.'"\s+data-confirm="Usunąć przekierowanie z adresu [^"]+">\s*<input type="hidden" name="_token"[^>]*>\s*<input type="hidden" name="_method" value="DELETE"#', $html);

        // Wiersz nie wymusza szerokości 0: przycisk może zawinąć się pod adres (reflow przy 320 px).
        $this->assertStringContainsString('<div class="min-w-48 flex-1">', $html);

        // Witryna główna nie ma dawnych adresów.
        $this->get(route('admin.witryny.nazwa', $this->main))->assertOk()->assertDontSee('Przekierowania ze starych adresów');
    }

    public function test_lista_dawnych_adresow_dotyczy_tylko_tej_witryny(): void
    {
        $other = $this->makeSite('Inna', 'inna', 'inna.example.test');
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->rename(['slug' => 'inna-nowa', 'domain' => 'inna-nowa.example.test'], $other)->assertSessionHasNoErrors();
        $otherRedirect = SiteAddressRedirect::query()->where('type', 'domain')->where('value', 'inna.example.test')->firstOrFail();
        $this->get(route('admin.witryny.index'))->assertOk(); // zużywa komunikat po zapisie (wymienia adresy drugiej witryny)

        $html = $this->get(route('admin.witryny.nazwa', $this->sub))->assertOk()->getContent();

        $this->assertStringContainsString('szkolenia.example.test', $html);
        $this->assertStringNotContainsString('inna.example.test', $html);
        $this->assertStringNotContainsString(route('admin.witryny.przekierowania.destroy', [$other, $otherRedirect]), $html);
    }

    public function test_lista_mowi_wprost_gdy_dawny_adres_jest_przykryty_przez_strone(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $html = $this->get(route('admin.witryny.nazwa', $this->sub))->assertOk()->getContent();
        $this->assertStringNotContainsString('Nie działa teraz', $html);

        Page::create(['site_id' => $this->main->id, 'title' => 'Szkolenia', 'slug' => 'szkolenia', 'type' => 'standard', 'is_published' => true]);

        $this->get(route('admin.witryny.nazwa', $this->sub))->assertOk()
            ->assertSee('Nie działa teraz: pod tym adresem jest strona.');
    }

    public function test_lista_mowi_wprost_gdy_witryna_nie_ma_adresu(): void
    {
        $this->rename(['slug' => '', 'domain' => ''])->assertSessionHasNoErrors();

        $this->get(route('admin.witryny.nazwa', $this->sub))->assertOk()
            ->assertSee('Witryna nie ma teraz adresu, więc te przekierowania nie działają.');
    }

    public function test_formularz_pelnej_edycji_mowi_o_przekierowaniu_tylko_przy_istniejacej_witrynie(): void
    {
        $this->actingAdmin()->get(route('admin.witryny.edit', $this->sub))->assertOk()
            ->assertSee('Po zmianie dotychczasowy adres będzie przekierowywał na nowy.')
            ->assertSee('Po zmianie stara domena przekieruje na nowy adres');

        $this->get(route('admin.witryny.create'))->assertOk()
            ->assertDontSee('Po zmianie dotychczasowy adres będzie przekierowywał na nowy.')
            ->assertDontSee('Po zmianie stara domena przekieruje');
    }

    public function test_usuniecie_przekierowania_wylacza_stary_adres_i_jest_zapisane_w_dzienniku(): void
    {
        $other = $this->makeSite('Inna', 'inna');
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->visit('/szkolenia')->assertStatus(301); // rozgrzewa mapę w pamięci podręcznej
        $redirect = SiteAddressRedirect::query()->where('type', 'slug')->firstOrFail();

        // Przekierowanie należy do innej witryny: ten adres nie pozwala go usunąć.
        $this->actingAdmin()->delete(route('admin.witryny.przekierowania.destroy', [$other, $redirect]))->assertNotFound();
        $this->assertNotNull($redirect->fresh());

        $this->delete(route('admin.witryny.przekierowania.destroy', [$this->sub, $redirect]))
            ->assertRedirect(route('admin.witryny.nazwa', $this->sub))
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'Usunięto przekierowanie'));

        $this->assertNull($redirect->fresh());
        $this->visit('/szkolenia')->assertNotFound();
        $this->assertNotNull(Activity::query()->where('description', 'like', 'Usunięto przekierowanie ze starego adresu witryny%')->first());
    }

    public function test_usuwanie_przekierowan_wymaga_administratora_sieci(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $redirect = SiteAddressRedirect::query()->where('type', 'slug')->firstOrFail();

        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $this->actingAs($editor)->delete(route('admin.witryny.przekierowania.destroy', [$this->sub, $redirect]))->assertForbidden();
        $this->assertNotNull($redirect->fresh());

        // Witryny w koszu nie edytuje się.
        $this->sub->forceFill(['trashed_at' => now()])->save();
        $this->actingAdmin()->delete(route('admin.witryny.przekierowania.destroy', [$this->sub, $redirect]))->assertNotFound();
        $this->assertNotNull($redirect->fresh());
    }

    public function test_przekierowania_nie_sa_trescia_witryny_i_znikaja_razem_z_nia(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $this->assertNotSame([], $this->redirectsOf($this->sub));

        // Tabela nie ma kolumny site_id, więc nie wymusza „Wymuś usunięcie” dla witryny bez treści.
        $this->assertArrayNotHasKey('site_address_redirects', SiteRemoval::contentCounts($this->sub->fresh()));

        SiteRemoval::remove($this->sub->fresh());

        $this->assertSame(0, SiteAddressRedirect::query()->count());
        $this->visit('/szkolenia')->assertNotFound();
    }

    public function test_przekierowanie_ma_jawny_cache_control_bo_przegladarki_pamietaja_301_bez_konca(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

        // Bez nagłówka Symfony usuwa domyślny Cache-Control dla 301 i przeglądarka zapamiętuje przekierowanie na stałe:
        // po powrocie do dawnego adresu (/centrum -> /szkolenia) zapętliłoby się, po usunięciu w panelu prowadziłoby donikąd.
        foreach ([$this->visit('/szkolenia/x'), $this->call('POST', $this->root().'/szkolenia/x')] as $response) {
            $cacheControl = (string) $response->headers->get('Cache-Control');
            $this->assertStringContainsString('no-cache', $cacheControl);
            $this->assertStringContainsString('private', $cacheControl);
        }
    }

    public function test_reczne_przekierowanie_ma_pierwszenstwo_przed_dawnym_adresem_skroconym(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        Redirect::create(['from_path' => '/szkolenia/regulamin', 'to_url' => '/dokumenty/regulamin.pdf', 'is_active' => true]);

        // Bardziej szczegółowa, jawna reguła administratora wygrywa z automatycznym przekierowaniem.
        $this->visit('/szkolenia/regulamin')->assertStatus(301)->assertRedirect('/dokumenty/regulamin.pdf');
        $this->visit('/szkolenia/inna-strona')->assertStatus(301)->assertRedirect($this->root().'/centrum/inna-strona');

        // Wyłączona reguła nie przeszkadza.
        Redirect::query()->update(['is_active' => false]);
        $this->visit('/szkolenia/regulamin')->assertStatus(301)->assertRedirect($this->root().'/centrum/regulamin');
    }

    public function test_adres_zajety_przez_inna_funkcje_serwisu_nie_jest_przekierowaniem(): void
    {
        $middleware = app(ResolveCurrentSite::class);
        $reaches = function (string $url) use ($middleware): bool {
            $this->resetCurrentSite();

            // Middleware przepuszcza żądanie dalej (nie robi przekierowania), gdy dawny adres jest przykryty.
            return $middleware->handle(Request::create($url), fn () => response('dalej'))->getContent() === 'dalej';
        };

        // Wiersze wpisane wprost (np. sprzed zmiany reguł albo po zmianie prefiksu panelu).
        foreach (['wiem', config('app.admin_prefix', 'admin'), 'api'] as $slug) {
            SiteAddressRedirect::create(['target_site_id' => $this->sub->id, 'type' => 'slug', 'value' => $slug]);
            $this->assertTrue($reaches($this->root().'/'.$slug.'/x'), 'Adres „'.$slug.'” należy do serwisu, nie do dawnej witryny.');
        }

        // Adres serwera jako dawna domena przekierowałby całą witrynę główną.
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        SiteAddressRedirect::create(['target_site_id' => $this->sub->id, 'type' => 'domain', 'value' => $appHost]);
        $this->assertTrue($reaches($this->root().'/aktualnosci'), 'Adres serwera nie jest dawną domeną witryny.');
        $this->assertSame('to adres serwera', SiteAddressRedirect::shadowReason('domain', $appHost));
    }

    public function test_krotki_adres_portalu_kategorii_wygrywa_z_dawna_domena_witryny(): void
    {
        config(['app.url' => 'https://feer.test']);
        Category::create(['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-i-warsztaty-test', 'portal_slug' => 'szkoleniatest']);
        SiteAddressRedirect::create(['target_site_id' => $this->sub->id, 'type' => 'domain', 'value' => 'szkoleniatest.feer.test']);
        SiteAddressRedirect::create(['target_site_id' => $this->sub->id, 'type' => 'domain', 'value' => 'inna.feer.test']);

        $this->assertSame('pod tym adresem działa portal kategorii', SiteAddressRedirect::shadowReason('domain', 'szkoleniatest.feer.test'));
        $this->assertNull(SiteAddressRedirect::shadowReason('domain', 'inna.feer.test'));

        // Portal: obsługuje go RedirectPortalSubdomain (302 na /portal/…), nie dawna domena witryny; inne hosty przekierowują.
        $this->resetCurrentSite();
        $this->get('https://szkoleniatest.feer.test/?utm=x')->assertStatus(302)->assertRedirect('https://feer.test/portal/szkoleniatest?utm=x');
        $this->resetCurrentSite();
        $this->get('https://inna.feer.test/o-nas')->assertStatus(301)->assertRedirect('https://szkolenia.example.test/o-nas');
    }

    public function test_usunieta_strona_nie_blokuje_przekierowania(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();
        $page = Page::create(['site_id' => $this->main->id, 'title' => 'Szkolenia', 'slug' => 'szkolenia', 'type' => 'standard', 'is_published' => true]);
        $this->assertFalse($this->visit('/szkolenia/x')->isRedirect(), 'Istniejąca strona wygrywa.');

        $page->delete();

        $this->visit('/szkolenia/x')->assertStatus(301)->assertRedirect($this->root().'/centrum/x');
    }

    public function test_przejecie_dawnej_domeny_przez_inna_witryne_jest_zapisane_w_komunikacie(): void
    {
        $other = $this->makeSite('Inna', 'inna');
        $this->rename(['slug' => 'szkolenia', 'domain' => 'kursy.example.test'])->assertSessionHasNoErrors();
        $this->assertContains('domain:szkolenia.example.test', $this->redirectsOf($this->sub));

        $this->rename(['slug' => 'inna', 'domain' => 'szkolenia.example.test'], $other)->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przestał przekierowywać na witrynę „Szkolenia”'));

        $this->assertNotContains('domain:szkolenia.example.test', $this->redirectsOf($this->sub));
        $this->assertFalse($this->visit('http://szkolenia.example.test/')->isRedirect(), 'Teraz to żywa domena innej witryny.');
    }

    public function test_powrot_do_wlasnego_dawnego_adresu_nie_mowi_ze_przerwano_cudze_przekierowanie(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

        $this->rename(['slug' => 'szkolenia', 'domain' => 'szkolenia.example.test'])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => ! str_contains($status, 'przestał przekierowywać'));
    }

    public function test_zmiana_samej_nazwy_po_zmianie_adresu_nie_powtarza_informacji_o_przekierowaniu(): void
    {
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przekierowuje teraz na nowy'));

        $this->rename(['slug' => 'centrum', 'domain' => '', 'site_name' => 'Centrum Szkoleń'])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => ! str_contains($status, 'przekierowuje teraz'));
    }

    public function test_pelna_edycja_zajmujaca_dawny_adres_innej_witryny_mowi_o_tym(): void
    {
        $other = $this->makeSite('Inna', 'inna');
        $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

        $this->actingAdmin()->put(route('admin.witryny.update', $other), ['site_name' => 'Inna', 'slug' => 'szkolenia', 'domain' => ''])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'przestał przekierowywać na witrynę „Szkolenia”'));

        $this->assertNotContains('slug:szkolenia', $this->redirectsOf($this->sub));
    }

    public function test_komunikat_po_zapisie_witryny_bez_adresu_nie_podaje_publicznego_adresu(): void
    {
        $this->rename(['slug' => '', 'domain' => ''])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => ! str_contains($status, 'Publiczny adres') && str_contains($status, 'zacznie przekierowywać'));

        $this->rename(['slug' => 'nowy', 'domain' => ''])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'Publiczny adres: '.$this->root().'/nowy'));
    }

    public function test_przed_migracja_zapis_i_strony_dzialaja_bez_tabeli_przekierowan(): void
    {
        $migration = require database_path('migrations/2026_11_03_100000_create_site_address_redirects_table.php');

        try {
            Schema::drop('site_address_redirects');
            Cache::flush();

            $this->rename(['slug' => 'centrum', 'domain' => ''])->assertSessionHasNoErrors();

            $this->assertSame('centrum', $this->sub->fresh()->slug);
            $this->visit('/centrum')->assertOk();
            $this->visit('/szkolenia')->assertNotFound();
            $this->assertFalse($this->visit('http://szkolenia.example.test/')->isRedirect());
            $this->actingAdmin()->get(route('admin.witryny.nazwa', $this->sub))->assertOk();
        } finally {
            $migration->up();
        }
    }
}
