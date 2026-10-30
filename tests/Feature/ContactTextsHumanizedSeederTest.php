<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Database\Seeders\ContactTextsHumanizedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTextsHumanizedSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_poprawia_teksty_kontaktu_i_jest_idempotentny(): void
    {
        $settings = SiteSetting::first() ?? SiteSetting::create([]);
        $settings->update([
            'contact_intro' => '<p>Masz pytania? Każda rozmowa to krok w&nbsp;stronę świata bez barier.</p><div class="tinyColumnSection"></div>',
            'contact_office_hours' => 'Poniedziałek - Środa 10:00 - 12:00',
            'contact_bank_accounts_note' => 'Opłaty za szkolenia firm i osób prawnych, a także za inne usługi. Reszta.',
        ]);

        $this->seed(ContactTextsHumanizedSeeder::class);
        $this->seed(ContactTextsHumanizedSeeder::class);

        $settings->refresh();
        $this->assertStringContainsString('Odezwij się.', $settings->contact_intro);
        $this->assertStringNotContainsString('tinyColumnSection', $settings->contact_intro);
        $this->assertSame('Od poniedziałku do środy, 10:00 do 12:00', $settings->contact_office_hours);
        $this->assertStringContainsString('Osoby fizyczne mają indywidualny numer rachunku wirtualnego', $settings->contact_bank_accounts_note);
    }

    public function test_nie_nadpisuje_tekstow_zmienionych_w_panelu(): void
    {
        $settings = SiteSetting::first() ?? SiteSetting::create([]);
        $settings->update(['contact_intro' => '<p>Własny wstęp.</p>', 'contact_office_hours' => 'Codziennie 9:00 do 17:00']);

        $this->seed(ContactTextsHumanizedSeeder::class);

        $settings->refresh();
        $this->assertSame('<p>Własny wstęp.</p>', $settings->contact_intro);
        $this->assertSame('Codziennie 9:00 do 17:00', $settings->contact_office_hours);
    }
}
