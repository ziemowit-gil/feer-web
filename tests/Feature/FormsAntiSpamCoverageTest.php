<?php

namespace Tests\Feature;

use App\Models\LandingPage;
use App\Models\MaterialSubscriber;
use App\Models\WebinarRegistration;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/** Antyspam (honeypot, żeton z czasem, proste zadanie tekstowe) w formularzach, które miały tylko limit zapytań. */
class FormsAntiSpamCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function token(int $answer = 8, int $ageSeconds = 10): string
    {
        return Crypt::encryptString(json_encode(['t' => now()->subSeconds($ageSeconds)->timestamp, 'a' => $answer]));
    }

    public function test_zapis_na_materialy_bot_bez_zetonu_dostaje_sukces_ale_adres_nie_jest_zapisany(): void
    {
        $this->post(route('materials.subscribe'), ['email' => 'bot@example.org'])->assertRedirect()->assertSessionHas('materials_subscribed');

        $this->assertSame(0, MaterialSubscriber::count());
    }

    public function test_zapis_na_materialy_bledna_odpowiedz_pokazuje_komunikat_a_poprawna_zapisuje(): void
    {
        $wrong = $this->from(route('materials.index'))->post(route('materials.subscribe'), [
            'email' => 'jan@example.org', SpamGuard::TOKEN_FIELD => $this->token(8), SpamGuard::ANSWER_FIELD => '5',
        ]);
        $wrong->assertSessionHasErrors('spam');
        $this->assertSame(0, MaterialSubscriber::count());

        $this->post(route('materials.subscribe'), [
            'email' => 'jan@example.org', SpamGuard::TOKEN_FIELD => $this->token(8), SpamGuard::ANSWER_FIELD => 'osiem',
        ])->assertSessionHas('materials_subscribed');
        $this->assertSame(1, MaterialSubscriber::count());
    }

    public function test_formularz_zapisu_na_materialy_ma_zadanie_antyspamowe_z_opisem(): void
    {
        $html = $this->get(route('materials.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="'.SpamGuard::ANSWER_FIELD.'"', $html);
        $this->assertStringContainsString('name="'.SpamGuard::TOKEN_FIELD.'"', $html);
        $this->assertStringContainsString('Pytanie chroni formularz przed automatycznym spamem', $html);
    }

    public function test_rejestracja_na_landing_page_odrzuca_bota_i_zle_zadanie_a_przyjmuje_poprawne(): void
    {
        LandingPage::create(['slug' => 'webinar-test', 'title' => 'Webinar', 'hero_title' => 'Webinar', 'is_published' => true]);
        $url = route('lp.register', 'webinar-test');
        $person = ['name' => 'Jan Test', 'email' => 'jan@example.org', 'consent' => 1];

        // Bot (brak żetonu): „sukces”, ale bez zapisu.
        $this->postJson($url, $person)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(0, WebinarRegistration::count());

        // Człowiek z błędnym wynikiem: błąd przy polu zadania.
        $this->postJson($url, $person + [SpamGuard::TOKEN_FIELD => $this->token(8), SpamGuard::ANSWER_FIELD => '3'])
            ->assertStatus(422)->assertJsonValidationErrors(SpamGuard::ANSWER_FIELD);
        $this->assertSame(0, WebinarRegistration::count());

        // Poprawny wynik słownie: zgłoszenie zapisane.
        $this->postJson($url, $person + [SpamGuard::TOKEN_FIELD => $this->token(8), SpamGuard::ANSWER_FIELD => 'osiem'])->assertOk();
        $this->assertSame(1, WebinarRegistration::count());
    }

    public function test_strona_landing_i_pytanie_o_dzialanie_maja_opis_zadania_antyspamowego(): void
    {
        LandingPage::create(['slug' => 'webinar-test', 'title' => 'Webinar', 'hero_title' => 'Webinar', 'is_published' => true]);

        $lp = $this->get('/lp/webinar-test')->assertOk()->getContent();
        $this->assertStringContainsString('Zadanie antyspamowe:', $lp);
        $this->assertStringContainsString('Pytanie chroni formularz przed automatycznym spamem', $lp);
    }
}
