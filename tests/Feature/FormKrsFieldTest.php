<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\KrsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FormKrsFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function odpis(): array
    {
        return ['odpis' => [
            'naglowekA' => ['numerKRS' => '0000123456', 'dataRejestracjiWKRS' => '01.02.2010'],
            'dane' => ['dzial1' => [
                'danePodmiotu' => [
                    'formaPrawna' => 'FUNDACJA',
                    'identyfikatory' => ['regon' => '14000000000000', 'nip' => '5250000000'],
                    'nazwa' => 'FUNDACJA PRZYKŁADOWA',
                    'czyPosiadaStatusOPP' => true,
                ],
                'siedzibaIAdres' => [
                    'siedziba' => ['wojewodztwo' => 'MAZOWIECKIE', 'miejscowosc' => 'WARSZAWA'],
                    'adres' => ['ulica' => 'UL. DŁUGA', 'nrDomu' => '5', 'nrLokalu' => '2', 'miejscowosc' => 'WARSZAWA', 'kodPocztowy' => '00-001'],
                    'adresStronyInternetowej' => 'WWW.PRZYKLAD.PL',
                ],
            ]],
        ]];
    }

    public function test_endpoint_zwraca_dane_podmiotu_z_rejestru_s_a_potem_p(): void
    {
        Http::fake([
            'api-krs.ms.gov.pl/*rejestr=S*' => Http::response([], 404),
            'api-krs.ms.gov.pl/*rejestr=P*' => Http::response($this->odpis()),
        ]);

        $this->getJson('/krs/123456')->assertOk()->assertJson(['data' => [
            'krs' => '0000123456',
            'nazwa' => 'FUNDACJA PRZYKŁADOWA',
            'nip' => '5250000000',
            'regon' => '140000000',
            'ulica' => 'UL. DŁUGA 5/2',
            'adres' => 'UL. DŁUGA 5/2, 00-001 WARSZAWA',
            'www' => 'https://www.przyklad.pl',
            'opp' => 'Tak',
        ]]);

        Http::assertSent(fn ($r) => str_contains($r->url(), '/OdpisAktualny/0000123456'));
    }

    public function test_brak_podmiotu_i_awaria_api(): void
    {
        Http::fake(fn ($request) => Http::response([], str_contains($request->url(), '0000000999') ? 404 : 500));

        $this->getJson('/krs/999')->assertNotFound()->assertJsonPath('message', 'Nie znaleziono podmiotu o tym numerze KRS.');
        $this->getJson('/krs/998')->assertStatus(503);

        $this->assertNull(KrsClient::normalize('0000000000'));
        $this->assertSame('0000000042', KrsClient::normalize('42'));
    }

    public function test_formularz_renderuje_pole_krs_i_pola_do_uzupelnienia(): void
    {
        $form = FormDefinition::create([
            'title' => 'Zgłoszenie organizacji', 'slug' => 'organizacja', 'is_active' => true,
            'fields' => [
                ['label' => 'Numer KRS', 'type' => 'krs', 'required' => true],
                ['label' => 'Nazwa organizacji', 'type' => 'text', 'krs_fill' => 'nazwa'],
                ['label' => 'NIP', 'type' => 'text', 'krs_fill' => 'nip'],
            ],
        ]);

        $this->get(route('formularz.show', $form->slug))->assertOk()
            ->assertSee('data-krs-input', false)
            ->assertSee('Pobierz dane z KRS')
            ->assertSee('data-krs-fill="nazwa"', false)
            ->assertSee('data-krs-fill="nip"', false)
            ->assertSee('aria-live="polite" data-krs-status', false);

        $rules = $form->validationRules(['numer_krs' => 'abc']);
        $this->assertContains('regex:/^\d{1,10}$/', $rules['data.numer_krs']);
    }

    public function test_kreator_zapisuje_pole_krs_i_mapowanie(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.formularze.store'), [
            'title' => 'KRS', 'slug' => 'krs-test', 'is_active' => '1',
            'fields' => [
                ['label' => 'Numer KRS', 'type' => 'krs'],
                ['label' => 'Nazwa', 'type' => 'text', 'krs_fill' => 'nazwa'],
                ['label' => 'Zgoda', 'type' => 'checkbox', 'krs_fill' => 'nazwa'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fields = FormDefinition::where('slug', 'krs-test')->firstOrFail()->fields;
        $this->assertSame('krs', $fields[0]['type']);
        $this->assertSame('nazwa', $fields[1]['krs_fill']);
        $this->assertNull($fields[2]['krs_fill'], 'Pola typu zgoda nie uzupełniamy z KRS.');
    }

    public function test_edytor_wizualny_ma_palete_podglad_i_panel_wlasciwosci(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $form = FormDefinition::create(['title' => 'Wizualny', 'slug' => 'wizualny', 'is_active' => true,
            'fields' => [['label' => 'Imię', 'type' => 'text', 'uid' => 'abcd1234']]]);

        $this->actingAs($admin)->get(route('admin.formularze.edit', $form))->assertOk()
            ->assertSee('role="tablist" aria-label="Sekcje formularza"', false)
            ->assertSee('id="panel-settings"', false)
            ->assertSee("addField('link')", false)
            ->assertSee('aria-label="Dodaj pole"', false)
            ->assertSee('aria-label="Podgląd formularza"', false)
            ->assertSee('id="field-props-heading"', false)
            ->assertSee("addField('krs')", false)
            ->assertSee("'fields[' + i + '][krs_fill]'", false);
    }
}
