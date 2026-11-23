<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\KreatywniOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\KreatywniTexts;
use App\Support\SpamGuard;
use Database\Seeders\KreatywniWorkshopOrderFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Modules\Contact\Support\ContactOptions;
use Tests\TestCase;

/**
 * Szablon „kreatywni”: przełącznik „Zamówienia online” w Ustawienia → Szablon. Wyłączony kieruje przyciski zamówienia
 * (oferta, menu „Oferta”, podstrona warsztatu) do kontaktu i zamyka formularz zamówienia warsztatów. Obok przełącznik
 * formularza kontaktowego (contact_options.form_enabled, to samo co Komunikacja → Kontakt).
 */
class KreatywniOrdersOnlineTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->resetCache();
        parent::tearDown();
    }

    private function resetCache(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function useTemplate(array $extra = []): SiteSetting
    {
        $this->resetCache();
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni'] + $extra)->save();
        $this->resetCache();

        return SiteSetting::current();
    }

    private function payload(array $texts): array
    {
        return [
            'site_name' => 'Fundacja „Kreatywni na co dzień”', 'brand_color' => '#1b4a07', 'header_layout' => 'classic', 'site_template' => 'kreatywni',
            'content_editor' => 'tinymce', 'mail_transport' => 'default', 'contact_email' => 'kontakt@example.pl',
            'kreatywni_texts' => $texts,
        ];
    }

    public function test_panel_wylacza_i_wlacza_zamowienia_online(): void
    {
        $this->useTemplate();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('Zamówienia online')
            ->assertSee('name="kreatywni_texts[_orders]" value="1" class="mt-1" aria-describedby="kc-orders-help" checked', false);

        $this->put(route('admin.ustawienia.update'), $this->payload(['_orders_form' => '1']))->assertSessionHasNoErrors();
        $this->resetCache();
        $this->assertTrue(SiteSetting::current()->kreatywni_texts['_orders_off']);
        $this->assertFalse(KreatywniTexts::for()->ordersOnline());
        $this->get(route('admin.ustawienia.edit'))->assertSee('Zamówienia online są teraz wyłączone.');

        $this->put(route('admin.ustawienia.update'), $this->payload(['_orders_form' => '1', '_orders' => '1']))->assertSessionHasNoErrors();
        $this->resetCache();
        $this->assertTrue(KreatywniTexts::for()->ordersOnline());
        $this->assertNull(KreatywniTexts::clean(['_orders_form' => '1', '_orders' => '1']), 'włączone to stan domyślny — nic nie zapisujemy');
    }

    public function test_wylaczone_zamowienia_prowadza_do_kontaktu_i_zamykaja_formularz(): void
    {
        $this->useTemplate(['contact_email' => 'warsztaty@example.org']);
        $offer = KreatywniOffer::create(['name' => 'Warsztaty mobilne', 'price' => 'od 30 zł', 'order' => 1, 'is_active' => true]);
        $offer->addItemsFromText('Świece sojowe | Przez cały rok');
        $this->seed(KreatywniWorkshopOrderFormSeeder::class);
        $form = FormDefinition::where('slug', KreatywniTexts::ORDER_FORM_SLUG)->firstOrFail();

        // Włączone: przycisk prowadzi do formularza, formularz otwarty.
        $this->assertSame(site_href('/formularz/zamowienie-warsztatow'), KreatywniOffer::forDisplay(KreatywniTexts::for())[0]['button_url']);
        $this->assertTrue($form->availability()['open']);
        $this->get('/warsztat/swiece-sojowe')->assertOk()->assertSee('/formularz/zamowienie-warsztatow', false);

        $this->useTemplate(['kreatywni_texts' => ['_orders_off' => true, 'orders_off_button' => 'Zadzwoń do nas']]);

        $display = KreatywniOffer::forDisplay(KreatywniTexts::for())[0];
        $this->assertNull($display['button_url']);
        $this->assertSame('Zadzwoń do nas', $display['button']);
        $this->get('/warsztat/swiece-sojowe')->assertOk()
            ->assertSee('Zadzwoń do nas')
            ->assertDontSee('/formularz/zamowienie-warsztatow', false);

        $availability = $form->fresh()->availability();
        $this->assertFalse($availability['open']);
        $this->assertSame('orders_off', $availability['reason']);
        $this->get(route('formularz.show', $form->slug))->assertOk()->assertSee('Zamówienia online są chwilowo wyłączone.');

        $this->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => ['warsztat' => 'Świece sojowe'],
        ])->assertSessionHasErrors('closed');
        $this->assertSame(0, $form->submissions()->count());
    }

    public function test_panel_szablonu_wylacza_formularz_kontaktowy_i_zostawia_inne_opcje(): void
    {
        $this->useTemplate(['contact_options' => ContactOptions::normalize(['form_enabled' => true, 'response_time' => 'Odpisujemy w 2 dni.'])]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->get(route('admin.ustawienia.edit'))->assertOk()
            ->assertSee('name="kreatywni_contact_form" value="1" class="mt-1" aria-describedby="kc-contact-form-help" checked', false);

        $this->put(route('admin.ustawienia.update'), $this->payload([]) + ['kreatywni_contact_form_toggle' => '1'])->assertSessionHasNoErrors();
        $this->resetCache();
        $options = ContactOptions::for();
        $this->assertFalse($options->formEnabled());
        $this->assertSame('Odpisujemy w 2 dni.', $options->responseTime());

        // Formularz z kreatora (slug kontakt) nie przyjmuje zgłoszeń, dawny formularz też nie.
        $form = \App\Support\ContactForm::definition();
        if ($form) {
            $this->assertSame('contact_off', $form->availability()['reason']);
        }
        $this->post(route('contact.store'), ['name' => 'Ala', 'email' => 'ala@example.org', 'message' => 'Dzień dobry'])
            ->assertSessionHasErrors('form');

        // Zapis innego formularza ustawień (bez znacznika) nie zmienia formularza kontaktowego.
        $this->put(route('admin.ustawienia.update'), $this->payload([]))->assertSessionHasNoErrors();
        $this->resetCache();
        $this->assertFalse(ContactOptions::for()->formEnabled());

        $this->put(route('admin.ustawienia.update'), $this->payload([]) + ['kreatywni_contact_form_toggle' => '1', 'kreatywni_contact_form' => '1'])->assertSessionHasNoErrors();
        $this->resetCache();
        $this->assertTrue(ContactOptions::for()->formEnabled());
    }
}
