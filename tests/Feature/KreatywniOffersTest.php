<?php

namespace Tests\Feature;

use App\Models\KreatywniOffer;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mikro moduł „Mikrooferta – Kreatywni”: panel (dodawanie, edycja, ukrywanie, kolejność, import z tekstów, usuwanie,
 * walidacja adresu), blok oferty na stronie głównej i na stronie „Oferta” z modułu albo z tekstów, działanie bez tabeli
 * i tylko w szablonie „kreatywni”.
 */
class KreatywniOffersTest extends TestCase
{
    use RefreshDatabase;

    private function useTemplate(string $template = 'kreatywni', array $extra = []): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => $template] + $extra)->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'name' => 'Warsztaty stacjonarne', 'price' => 'od 45 zł', 'price_unit' => 'za grupę', 'price_note' => 'w naszej pracowni',
            'features' => "Kawa dla opiekunów\nParking", 'items' => "Ceramika | Przez cały rok\nPisanki | Wielkanoc\nOrigami",
            'items_style' => 'list', 'button_label' => 'Zapisz grupę', 'button_url' => '/kontakt', 'is_active' => '1',
        ];
    }

    public function test_panel_dodaje_oferte_a_strona_glowna_i_oferta_ja_pokazuja(): void
    {
        $this->useTemplate();
        Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Opisy warsztatów.</p>']);
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-oferty.index'))->assertOk()
            ->assertSee('Mikrooferta – Kreatywni')->assertSee('Przenieś ofertę z tekstów');
        $this->get(route('admin.kreatywni-oferty.create'))->assertOk()
            ->assertSee('<label for="ko-name"', false)
            ->assertSee('name="items_style" value="chips"', false)
            ->assertSee('value="Zamów warsztaty"', false);

        $this->post(route('admin.kreatywni-oferty.store'), $this->payload())
            ->assertRedirect(route('admin.kreatywni-oferty.index'))->assertSessionHasNoErrors();
        $offer = KreatywniOffer::query()->firstOrFail();
        $this->assertSame(['Przez cały rok' => ['Ceramika'], 'Wielkanoc' => ['Pisanki'], 'Warsztaty' => ['Origami']], $offer->itemGroups());

        foreach (['/', '/oferta'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('<p class="kc-price-name">Warsztaty stacjonarne</p>', $html, $url);
            $this->assertStringContainsString('<strong>od 45 zł</strong> <span>za grupę</span>', $html, $url);
            $this->assertStringContainsString('<li>Kawa dla opiekunów</li>', $html, $url);
            $this->assertStringContainsString('<ul class="kc-plain-list">', $html, $url);
            $this->assertStringContainsString('>Zapisz grupę <i', $html, $url);
            $this->assertStringNotContainsString('Warsztaty mobilne', $html, 'Oferta z modułu zastępuje ofertę z tekstów ('.$url.').');
        }
        $this->get('/')->assertSee('<h3 class="kc-collection-head">Wielkanoc</h3>', false);
        $this->get('/oferta')->assertSee('<h2 class="kc-collection-head">Wielkanoc</h2>', false);
    }

    public function test_import_z_tekstow_kolejnosc_ukrywanie_i_usuwanie(): void
    {
        $this->useTemplate(extra: ['kreatywni_texts' => ['_offer_style' => 'list']]);
        $this->actingAs($this->admin());

        $this->post(route('admin.kreatywni-oferty.import'))->assertRedirect()->assertSessionHas('status');
        $imported = KreatywniOffer::query()->firstOrFail();
        $this->assertSame(['Warsztaty mobilne', 'od 30 zł', 'list', 'Zamów warsztaty', null], [$imported->name, $imported->price, $imported->items_style, $imported->button_label, $imported->button_url]);
        $this->assertSame(16, $imported->itemCount());
        $this->post(route('admin.kreatywni-oferty.import'));
        $this->assertSame(1, KreatywniOffer::query()->count(), 'Drugi import nic nie dubluje.');

        $this->post(route('admin.kreatywni-oferty.store'), $this->payload(['items_style' => 'chips']))->assertSessionHasNoErrors();
        $second = KreatywniOffer::query()->where('name', 'Warsztaty stacjonarne')->firstOrFail();
        $this->post(route('admin.kreatywni-oferty.przesun', $second), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Warsztaty stacjonarne', 'Warsztaty mobilne'], KreatywniOffer::query()->orderBy('order')->pluck('name')->all());

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Warsztaty mobilne'), strpos($html, 'Warsztaty stacjonarne'), 'Kolejność z panelu.');
        $this->assertStringContainsString('<ul class="kc-chips">', $html, 'Każda oferta ma własny wygląd warsztatów.');
        $this->assertStringContainsString('<ul class="kc-plain-list">', $html);

        $this->put(route('admin.kreatywni-oferty.update', $imported), $this->payload(['name' => 'Warsztaty mobilne', 'is_active' => '0']))->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('Warsztaty mobilne', $this->get('/')->getContent(), 'Ukryta oferta znika ze strony.');

        $this->put(route('admin.kreatywni-oferty.update', $second), $this->payload(['is_active' => '0']))->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertDontSee('id="oferta"', false);
        $this->get(route('admin.kreatywni-oferty.index'))->assertSee('Wszystkie oferty są ukryte');

        $this->delete(route('admin.kreatywni-oferty.destroy', $second))->assertRedirect();
        $this->assertSame(['Warsztaty mobilne'], KreatywniOffer::query()->pluck('name')->all());
    }

    public function test_walidacja_adresu_przycisku_i_wygladu(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());

        $this->post(route('admin.kreatywni-oferty.store'), $this->payload(['button_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('button_url');
        $this->post(route('admin.kreatywni-oferty.store'), $this->payload(['items_style' => 'kafle', 'name' => '']))
            ->assertSessionHasErrors(['items_style', 'name']);
        $this->assertSame(0, KreatywniOffer::query()->count());

        // Pusty adres = strona kontaktu, pusty napis = bez przycisku.
        $this->post(route('admin.kreatywni-oferty.store'), $this->payload(['button_url' => '', 'button_label' => '']))->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('class="kc-btn kc-btn-sun kc-btn-block"', $this->get('/')->getContent());
    }

    public function test_bez_ofert_w_module_strona_bierze_oferte_z_tekstow(): void
    {
        $this->useTemplate();

        $this->get('/')->assertOk()->assertSee('<p class="kc-price-name">Warsztaty mobilne</p>', false)->assertSee('<ul class="kc-chips">', false);
    }

    public function test_dziala_przed_migracja_i_tylko_w_szablonie_kreatywni(): void
    {
        $this->useTemplate('default');
        $this->actingAs($this->admin())->get(route('admin.kreatywni-oferty.index'))->assertNotFound();

        $this->useTemplate();
        $this->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.kreatywni-oferty.index'), false);

        Schema::drop('kreatywni_offers');
        \Illuminate\Support\Once::flush();
        $this->get('/')->assertOk()->assertSee('Warsztaty mobilne');
        $this->get(route('admin.kreatywni-oferty.index'))->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.kreatywni-oferty.index'), false);
    }

    public function test_oferta_innej_witryny_jest_niedostepna(): void
    {
        $this->useTemplate();
        $other = KreatywniOffer::withoutGlobalScopes()->create(['site_id' => 9999, 'name' => 'Obca oferta', 'order' => 1, 'is_active' => true]);

        $this->actingAs($this->admin())->get(route('admin.kreatywni-oferty.edit', $other))->assertNotFound();
    }

    public function test_migracja_przenosi_oferte_z_tekstow_do_pustego_modulu_witryny_kreatywni(): void
    {
        $site = $this->useTemplate(extra: ['kreatywni_texts' => ['offer_card_title' => 'Warsztaty w szkole', '_offer_style' => 'list']]);
        $migration = require base_path('database/migrations/2026_11_23_120000_import_kreatywni_offers_from_texts.php');

        $migration->up();
        $offer = KreatywniOffer::withoutGlobalScopes()->where('site_id', $site->id)->sole();
        $this->assertSame(['Warsztaty w szkole', 'od 30 zł', 'list', 1, true], [$offer->name, $offer->price, $offer->items_style, $offer->order, $offer->is_active]);

        // Ponowne uruchomienie nie dubluje, a zmieniona w panelu oferta zostaje.
        $offer->update(['name' => 'Zmieniona w panelu']);
        $migration->up();
        $this->assertSame(['Zmieniona w panelu'], KreatywniOffer::withoutGlobalScopes()->where('site_id', $site->id)->pluck('name')->all());

        // Witryna w innym szablonie — bez importu.
        KreatywniOffer::query()->delete();
        $this->useTemplate('default');
        $migration->up();
        $this->assertSame(0, KreatywniOffer::query()->count());
    }

    public function test_ustawienia_i_pasek_panelu_prowadza_do_mikrooferty(): void
    {
        $this->useTemplate();
        $this->actingAs($this->admin());

        $this->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('Mikrooferta jest pusta, więc sekcja „Oferta warsztatów” pokazuje kartę i warsztaty z tekstów poniżej.')
            ->assertSee('Przenieś je do Mikrooferty');
        $this->get('/')->assertOk()->assertSee('Zarządzaj ofertą warsztatów (Mikrooferta)')->assertSee(route('admin.kreatywni-oferty.index'), false);

        $this->post(route('admin.kreatywni-oferty.import'))->assertSessionHas('status');
        $this->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('Kartę cenową i warsztaty w sekcji „Oferta warsztatów” (strona główna i góra strony „Oferta”) bierzemy z')
            ->assertSee('Oferty z Mikrooferty mają własny wygląd warsztatów');
    }
}
