<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\KreatywniOffer;
use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Database\Seeders\KreatywniWorkshopOrderFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Seeder formularza „Zamówienie warsztatów” (moduł Formularze, szablon „kreatywni”): lista warsztatów z Mikrooferty
 * witryny, e-mail powiadomień z kontaktu witryny, przycisk zamówienia ofert bez adresu, pole adresu tylko przy
 * zajęciach u zamawiającego, brak nadpisywania zmian z panelu.
 */
class KreatywniWorkshopOrderFormSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    private function submit(FormDefinition $form, array $data)
    {
        return $this->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => $data,
        ]);
    }

    public function test_formularz_z_warsztatami_z_oferty_ktory_dziala_i_nie_nadpisuje_zmian(): void
    {
        $site = SiteSetting::current();
        $site->forceFill(['site_template' => 'kreatywni', 'contact_email' => 'warsztaty@example.org'])->save();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $offer = KreatywniOffer::create(['name' => 'Warsztaty mobilne', 'order' => 1, 'is_active' => true]);
        $offer->addItemsFromText("Świece sojowe | Przez cały rok\nBombki, jajka i wianki | Święta\nUkryty warsztat");
        $offer->cards()->where('title', 'Ukryty warsztat')->update(['is_active' => false]);
        $own = KreatywniOffer::create(['name' => 'Urodziny', 'button_url' => 'https://example.org/urodziny', 'order' => 2, 'is_active' => true]);

        $this->seed(KreatywniWorkshopOrderFormSeeder::class);
        $form = FormDefinition::where('slug', KreatywniWorkshopOrderFormSeeder::SLUG)->firstOrFail();

        $fields = collect($form->inputFields())->keyBy('key');
        $this->assertSame(['Świece sojowe', 'Bombki jajka i wianki', KreatywniWorkshopOrderFormSeeder::UNDECIDED], FormDefinition::fieldOptions($fields['warsztat']));
        $this->assertSame('warsztaty@example.org', $form->settings['notification_email']);
        $this->assertSame('/formularz/zamowienie-warsztatow', $offer->fresh()->button_url, 'oferta bez adresu przycisku prowadzi do formularza');
        $this->assertSame('https://example.org/urodziny', $own->fresh()->button_url, 'własnego adresu nie zmienia');

        $this->get(route('formularz.show', $form->slug))->assertOk()
            ->assertSee('Zamówienie warsztatów')->assertSee('Liczba uczestników')->assertSee('Świece sojowe')
            ->assertSee('data-ff-if="miejsce_zajec"', false);

        $consentKey = $fields->firstWhere('type', 'consent')['key'];
        $data = [
            'warsztat' => 'Świece sojowe', 'preferowany_termin' => '2026-12-10', 'liczba_uczestnikow' => '20', 'wiek_uczestnikow' => '5–6 lat',
            'miejsce_zajec' => 'W naszej pracowni', 'imie_i_nazwisko' => 'Anna Kowalska', 'adres_e_mail' => 'anna@example.org', 'numer_telefonu' => '600100200',
            $consentKey => '1',
        ];
        $this->assertEmpty(array_diff(array_keys($data), $fields->keys()->all()), 'Klucze pól: '.$fields->keys()->implode(', '));
        $this->submit($form, $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $form->submissions()->count());

        // Zajęcia u zamawiającego — adres wymagany; warsztat spoza listy odrzucony.
        $this->submit($form, ['miejsce_zajec' => 'U Was w placówce lub firmie (dojeżdżamy z materiałami)', 'warsztat' => 'Ukryty warsztat'] + $data)
            ->assertSessionHasErrors(['data.adres_zajec', 'data.warsztat']);

        // Ponowne uruchomienie nie nadpisuje zmian z panelu.
        $form->update(['title' => 'Zmieniony w panelu']);
        $this->seed(KreatywniWorkshopOrderFormSeeder::class);
        $this->assertSame('Zmieniony w panelu', $form->fresh()->title);
        $this->assertSame(1, FormDefinition::where('slug', KreatywniWorkshopOrderFormSeeder::SLUG)->count());
    }

    public function test_bez_witryny_kreatywni_lista_domyslna_szablonu_i_bez_adresu_powiadomien(): void
    {
        $this->seed(KreatywniWorkshopOrderFormSeeder::class);
        $form = FormDefinition::where('slug', KreatywniWorkshopOrderFormSeeder::SLUG)->firstOrFail();
        $options = FormDefinition::fieldOptions(collect($form->inputFields())->firstWhere('key', 'warsztat'));

        $this->assertContains('Laleczki zapachowe', $options);
        $this->assertContains('Dekorowanie bombek', $options);
        $this->assertSame(KreatywniWorkshopOrderFormSeeder::UNDECIDED, end($options));
        $this->assertSame('', $form->settings['notification_email']);
    }
}
