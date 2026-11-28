<?php

namespace Tests\Feature;

use App\Models\KreatywniOffer;
use App\Models\KreatywniOfferItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Oferta Kreatywnych: opisy warsztatu z edytora WYSIWYG (pogrubienie i kursywa w krótkim opisie, pełniejsze formatowanie
 * na podstronie, HTML czyszczony), SEO podstrony /warsztat/{slug} i SEO strony „Oferta” z formularza oferty.
 */
class KreatywniOfferRichTextSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni'])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    private function offer(): KreatywniOffer
    {
        return KreatywniOffer::create(['name' => 'Warsztaty mobilne', 'price' => 'od 30 zł', 'items_style' => 'cards', 'order' => 1, 'is_active' => true]);
    }

    public function test_edytor_pogrubienie_czyszczenie_html_i_seo_warsztatu(): void
    {
        $offer = $this->offer();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(route('admin.kreatywni-oferty.warsztaty.create', $offer))->assertOk()
            ->assertSee('tinymce@7', false)->assertSee('Tytuł SEO')->assertSee('Opis SEO');

        $this->post(route('admin.kreatywni-oferty.warsztaty.store', $offer), [
            'title' => 'Świece sojowe',
            'description' => '<p>Relaks z <strong>ekologicznych</strong> materiałów <a href="https://x.pl">link</a><script>alert(1)</script></p>',
            'details' => '<h2>Przebieg</h2><p onclick="x()">Zajęcia <em>trwają</em> 2 h.</p><ul><li>wosk</li></ul><img src=x onerror=alert(1)>',
            'meta_title' => 'Warsztaty świece sojowe Kraków',
            'meta_description' => 'Zrób własną świecę sojową z ekologicznych materiałów.',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $item = KreatywniOfferItem::firstOrFail();
        $this->assertSame('Relaks z <strong>ekologicznych</strong> materiałów link', $item->description, 'krótki opis: tylko pogrubienie i kursywa');
        $this->assertStringNotContainsString('onclick', (string) $item->details);
        $this->assertStringNotContainsString('<img', (string) $item->details);
        $this->assertStringContainsString('<h2>Przebieg</h2>', (string) $item->details);
        $this->assertSame('Warsztaty świece sojowe Kraków', $item->meta_title);
        $this->assertStringEndsWith('| Relaks z ekologicznych materiałów link', (string) $offer->fresh()->items, 'tekstowy zapas bez znaczników');

        $page = $this->get('/warsztat/swiece-sojowe')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Warsztaty świece sojowe Kraków — ', $page);
        $this->assertStringContainsString('<meta name="description" content="Zrób własną świecę sojową', $page);
        $this->assertStringContainsString('"@type":"Service"', $page);
        $this->assertStringContainsString('<strong>ekologicznych</strong>', $page);
        $this->assertStringContainsString('<li>wosk</li>', $page);
        $this->assertStringNotContainsString('alert(1)', $page);

        // Karta w bloku oferty: pogrubienie widoczne.
        $groups = $offer->fresh()->itemGroups();
        $this->assertSame('Relaks z <strong>ekologicznych</strong> materiałów link', $groups[array_key_first($groups)][0]['descHtml']);
    }

    public function test_stary_zwykly_tekst_dalej_dziala_i_bez_seo_opis_z_tresci(): void
    {
        $offer = $this->offer();
        $offer->cards()->create(['title' => 'Bombki', 'description' => 'Ozdabianie bombek & zabawa, dzieci < 10 lat', 'details' => "Akapit 1.\n\nAkapit 2.", 'order' => 1, 'is_active' => true]);

        $page = $this->get('/warsztat/bombki')->assertOk()->getContent();
        $this->assertStringContainsString('Ozdabianie bombek &amp; zabawa, dzieci &lt; 10 lat', $page);
        $this->assertStringContainsString('<p>Akapit 1.</p>', $page);
        $this->assertStringContainsString('<p>Akapit 2.</p>', $page);
        $this->assertStringContainsString('<meta name="description" content="Ozdabianie bombek &amp; zabawa, dzieci &lt; 10 lat">', $page);
    }

    public function test_seo_strony_oferta_z_formularza_oferty(): void
    {
        $offer = $this->offer();
        $page = Page::create(['title' => 'Oferta', 'slug' => 'oferta', 'type' => 'standard', 'is_published' => true, 'content' => '<p>Oferta.</p>']);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(route('admin.kreatywni-oferty.edit', $offer))->assertOk()->assertSee('Tytuł SEO')->assertSee('„Oferta”', false);
        $this->put(route('admin.kreatywni-oferty.update', $offer), [
            'name' => 'Warsztaty mobilne', 'items_style' => 'cards', 'is_active' => '1',
            'meta_title' => 'Warsztaty kreatywne z dojazdem — oferta',
            'meta_description' => 'Mobilne warsztaty kreatywne dla dzieci, dorosłych i seniorów.',
        ])->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('Warsztaty kreatywne z dojazdem — oferta', $page->meta_title);
        $this->assertSame('Mobilne warsztaty kreatywne dla dzieci, dorosłych i seniorów.', $page->meta_description);
    }

    public function test_faq_warsztatu_w_panelu_i_na_podstronie(): void
    {
        $offer = $this->offer();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(route('admin.kreatywni-oferty.warsztaty.create', $offer))->assertOk()
            ->assertSee('Najczęstsze pytania (FAQ)')->assertSee('Dodaj pytanie');

        $this->post(route('admin.kreatywni-oferty.warsztaty.store', $offer), [
            'title' => 'Bombki', 'is_active' => '1',
            'faq' => [
                ['q' => 'Ile trwają zajęcia?', 'a' => "Około 2 godzin.\n\nMożna dłużej."],
                ['q' => 'Pytanie bez odpowiedzi', 'a' => ''],
                ['q' => 'Co zabrać? <b>', 'a' => 'Nic — materiały są w cenie.'],
            ],
        ])->assertSessionHasNoErrors();

        $item = KreatywniOfferItem::firstOrFail();
        $this->assertCount(2, $item->faqItems(), 'niepełna para pominięta');

        $page = $this->get('/warsztat/bombki')->assertOk()->getContent();
        $this->assertStringContainsString('Najczęstsze pytania', $page);
        $this->assertStringContainsString('<summary>Ile trwają zajęcia?</summary>', $page);
        $this->assertStringContainsString('<p>Około 2 godzin.</p>', $page);
        $this->assertStringContainsString('Co zabrać? &lt;b&gt;', $page);
        $this->assertStringContainsString('"@type":"FAQPage"', $page);
        $this->assertStringNotContainsString('Pytanie bez odpowiedzi', $page);

        $item->update(['faq' => null]);
        $this->assertStringNotContainsString('FAQPage', $this->get('/warsztat/bombki')->getContent());
    }
}
