<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Database\Seeders\NgoSupportFormSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class NgoSupportFormSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    public function test_seeder_dodaje_formularz_ktory_dziala_i_nie_nadpisuje_zmian(): void
    {
        $this->seed(NgoSupportFormSeeder::class);
        $form = FormDefinition::where('slug', NgoSupportFormSeeder::SLUG)->firstOrFail();

        $this->get(route('formularz.show', $form->slug))->assertOk()
            ->assertSee('Wsparcie technologiczne dla NGO')
            ->assertSee('Dostępność cyfrowa (WCAG)')
            ->assertSee('Spotkanie online')
            ->assertSee('data-krs-fill="nazwa"', false);

        $keys = array_column($form->inputFields(), 'key');
        $data = [
            'imie_i_nazwisko' => 'Anna Kowalska',
            'nazwa_organizacji_ngo' => 'Fundacja Testowa',
            'twoja_rola_w_organizacji' => 'Zarząd',
            'adres_e_mail' => 'anna@example.org',
            'w_czym_mozemy_pomoc' => ['Strona WWW / CMS', 'Inne'],
            'opisz_wyzwanie_techniczne' => 'Strona nie działa.',
            'preferowany_sposob_kontaktu' => 'E-mail',
        ];
        $consentKey = collect($form->inputFields())->firstWhere('type', 'consent')['key'];
        $data[$consentKey] = '1';
        $this->assertEmpty(array_diff(array_keys($data), $keys), 'Klucze pól: '.implode(', ', $keys));

        $this->post(route('formularz.store', $form->slug), [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => '8',
            'data' => $data,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $form->submissions()->count());

        // Ponowne uruchomienie nie nadpisuje zmian z panelu.
        $form->update(['title' => 'Zmieniony w panelu']);
        $this->seed(NgoSupportFormSeeder::class);
        $this->assertSame('Zmieniony w panelu', $form->fresh()->title);
        $this->assertSame(1, FormDefinition::where('slug', NgoSupportFormSeeder::SLUG)->count());
    }
}
