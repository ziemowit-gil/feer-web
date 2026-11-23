<?php

namespace Tests\Feature;

use App\Models\KreatywniOffer;
use App\Models\KreatywniOfferItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Przebudowa „Mikrooferty – Kreatywni”: warsztaty oferty jako karty (grafika, nazwa, grupa, opis, cena, kolejność,
 * widoczność), podstrona /warsztat/{slug}, przeniesienie tekstu migracją, hurtowe dodawanie i działanie bez tabeli kart.
 */
class KreatywniOfferItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    private function useTemplate(string $template = 'kreatywni'): SiteSetting
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => $template])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return SiteSetting::current();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function offer(string $items = ''): KreatywniOffer
    {
        $offer = KreatywniOffer::create(['name' => 'Warsztaty mobilne', 'price' => 'od 30 zł', 'price_unit' => 'za osobę', 'items_style' => 'chips', 'order' => 1, 'is_active' => true]);
        $offer->addItemsFromText($items);

        return $offer;
    }

    public function test_migracja_przenosi_wiersze_tekstu_do_kart_z_adresami(): void
    {
        $this->useTemplate();
        $offer = KreatywniOffer::create(['name' => 'Oferta', 'items' => "Świece sojowe | Przez cały rok | Relaks z woskiem.\nBombki | Boże Narodzenie\nOrigami", 'order' => 1, 'is_active' => true]);
        $migration = require base_path('database/migrations/2026_11_23_180000_create_kreatywni_offer_items_table.php');

        $migration->up();
        $cards = $offer->cards()->get();
        $this->assertSame(['Świece sojowe', 'Bombki', 'Origami'], $cards->pluck('title')->all());
        $this->assertSame(['swiece-sojowe', 'bombki', 'origami'], $cards->pluck('slug')->all());
        $this->assertSame(['Relaks z woskiem.', null, null], $cards->pluck('description')->all());
        $this->assertSame(['Przez cały rok', 'Boże Narodzenie', null], $cards->pluck('group')->all());
        $migration->up();
        $this->assertSame(3, $offer->cards()->count(), 'ponowne uruchomienie nie dubluje');
    }

    public function test_panel_karty_warsztatow_z_grafika_cena_kolejnoscia_i_widocznoscia(): void
    {
        $this->useTemplate();
        $offer = $this->offer("Świece sojowe | Przez cały rok\nBombki | Boże Narodzenie");
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-oferty.edit', $offer))->assertOk()
            ->assertSee('Warsztaty w tej ofercie')->assertSee('Dodaj kilka warsztatów naraz')->assertSee('Świece sojowe');
        $this->get(route('admin.kreatywni-oferty.warsztaty.create', $offer))->assertOk()
            ->assertSee('<label for="ki-title"', false)->assertSee('name="image_library_id"', false)->assertSee('<datalist id="ki-groups">', false);

        $this->post(route('admin.kreatywni-oferty.warsztaty.store', $offer), [
            'title' => 'Perfumy', 'group' => 'Przez cały rok', 'description' => 'Pachnące zajęcia.', 'details' => "Akapit 1.\n\nAkapit 2.",
            'price' => 'od 45 zł', 'image' => UploadedFile::fake()->image('perfumy.jpg', 900, 600), 'image_alt' => 'Flakoniki perfum', 'is_active' => '1',
        ])->assertRedirect(route('admin.kreatywni-oferty.edit', $offer).'#warsztaty')->assertSessionHasNoErrors();

        $perfumy = KreatywniOfferItem::query()->where('title', 'Perfumy')->sole();
        $this->assertSame('perfumy', $perfumy->slug);
        $this->assertNotNull($perfumy->imageUrl());
        $this->assertStringContainsString('Perfumy | Przez cały rok | Pachnące zajęcia.', (string) $offer->fresh()->items, 'tekstowy zapas odświeżony');

        $this->post(route('admin.kreatywni-oferty.warsztaty.przesun', [$offer, $perfumy]), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Świece sojowe', 'Perfumy', 'Bombki'], $offer->cards()->pluck('title')->all());

        $bombki = $offer->cards()->where('title', 'Bombki')->sole();
        $this->put(route('admin.kreatywni-oferty.warsztaty.update', [$offer, $bombki]), ['title' => 'Bombki', 'group' => 'Boże Narodzenie', 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->assertSame(['Przez cały rok'], array_keys($offer->fresh()->itemGroups()), 'ukryty warsztat znika z oferty');

        $this->delete(route('admin.kreatywni-oferty.warsztaty.destroy', [$offer, $bombki]))->assertRedirect();
        $this->assertSame(2, $offer->cards()->count());

        // Hurtowe dodanie przez pole w formularzu oferty — na końcu listy.
        $this->put(route('admin.kreatywni-oferty.update', $offer), ['name' => 'Warsztaty mobilne', 'items_style' => 'chips', 'items' => "Kule musujące | Przez cały rok | Buzujące kule.", 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Świece sojowe', 'Perfumy', 'Kule musujące'], $offer->cards()->pluck('title')->all());

        // Walidacja i karta innej oferty.
        $this->post(route('admin.kreatywni-oferty.warsztaty.store', $offer), ['title' => '', 'image' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')])->assertSessionHasErrors(['title', 'image']);
        $other = KreatywniOffer::create(['name' => 'Inna', 'order' => 2, 'is_active' => true]);
        $this->get(route('admin.kreatywni-oferty.warsztaty.edit', [$other, $perfumy]))->assertNotFound();
    }

    public function test_karty_z_grafikami_na_stronie_glownej_i_podstrona_warsztatu(): void
    {
        $this->useTemplate();
        Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'standard', 'is_published' => true, 'content' => '']);
        $offer = $this->offer("Świece sojowe | Przez cały rok | Relaks z woskiem.\nBombki | Przez cały rok | Ozdabianie bombek.\nWianki | Wielkanoc");
        $swiece = $offer->cards()->where('title', 'Świece sojowe')->sole();
        $swiece->update(['price' => 'od 40 zł', 'details' => "Pierwszy akapit opisu.\n\nDrugi akapit opisu.", 'image_alt' => 'Świece w słoikach']);
        $swiece->addMedia(UploadedFile::fake()->image('swiece.jpg', 900, 600))->toMediaCollection('image');

        // Wygląd „pigułki”, ale jest grafika — karty.
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('<ul class="kc-offer-cards">', $html);
        $this->assertMatchesRegularExpression('#<img class="kc-offer-img" src="[^"]+" alt="Świece w słoikach" loading="lazy">#', $html);
        $this->assertStringContainsString('<h4 class="kc-offer-name"><a href="'.$swiece->publicUrl().'">Świece sojowe</a></h4>', $html);
        $this->assertStringContainsString('<p class="kc-offer-price">od 40 zł</p>', $html);
        $this->get('/oferta')->assertOk()->assertSee('<h3 class="kc-offer-name">', false);

        $page = $this->get('/warsztat/swiece-sojowe')->assertOk()->getContent();
        $this->assertStringContainsString('<h1 id="kc-page-title">Świece sojowe</h1>', $page);
        $this->assertStringContainsString('<p class="kc-lead">Relaks z woskiem.</p>', $page);
        $this->assertStringContainsString('alt="Świece w słoikach"', $page);
        $this->assertStringContainsString('<strong>od 40 zł</strong>', $page);
        $this->assertStringContainsString('<p>Drugi akapit opisu.</p>', $page);
        $this->assertStringContainsString('Wróć do oferty warsztatów', $page);
        $this->assertStringContainsString('Inne warsztaty: Przez cały rok', $page);
        $this->assertStringContainsString('Bombki', $page);
        $this->assertStringNotContainsString('<strong>Wianki</strong>', $page, 'inne warsztaty z tej samej grupy');

        // Bez dłuższego opisu podstrona pokazuje krótki; cena z karty oferty.
        $this->get('/warsztat/bombki')->assertOk()->assertSee('<p>Ozdabianie bombek.</p>', false)->assertSee('<strong>od 30 zł za osobę</strong>', false);

        // Ukryty warsztat, ukryta oferta, inny szablon — 404.
        $offer->cards()->where('title', 'Wianki')->update(['is_active' => false]);
        $this->get('/warsztat/wianki')->assertNotFound();
        $offer->update(['is_active' => false]);
        $this->get('/warsztat/swiece-sojowe')->assertNotFound();
        $offer->update(['is_active' => true]);
        $this->useTemplate('default');
        $this->get('/warsztat/swiece-sojowe')->assertNotFound();
    }

    public function test_bez_tabeli_kart_dziala_tekst_jak_dotad(): void
    {
        $this->useTemplate();
        $offer = KreatywniOffer::create(['name' => 'Oferta', 'items' => "Świece | Przez cały rok | Opis.", 'items_style' => 'list', 'order' => 1, 'is_active' => true]);
        Schema::drop('kreatywni_offer_items');
        \Illuminate\Support\Once::flush();
        $this->actingAs($this->admin());

        $this->get(route('admin.kreatywni-oferty.edit', $offer))->assertOk()->assertSee('Lista warsztatów')->assertDontSee('Warsztaty w tej ofercie');
        $this->get('/')->assertOk()->assertSee('<span class="kc-offer-name">Świece</span>', false);
        $this->get('/warsztat/swiece')->assertNotFound();
    }
}
