<?php

namespace Tests\Feature;

use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class FormSpamGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function form(): FormDefinition
    {
        return FormDefinition::create([
            'title'     => 'Zgłoszenie',
            'slug'      => 'zgloszenie',
            'fields'    => [
                ['label' => 'Imię', 'type' => 'text', 'required' => true],
                ['label' => 'Treść', 'type' => 'textarea', 'required' => true],
            ],
            'is_active' => true,
        ]);
    }

    /** Żeton taki, jaki wystawia strona: czas wygenerowania + wynik zadania. */
    private function token(int $answer, int $secondsAgo = 10): string
    {
        return Crypt::encryptString(json_encode([
            't' => now()->subSeconds($secondsAgo)->timestamp,
            'a' => $answer,
        ]));
    }

    /** @param array<string, mixed> $payload */
    private function send(FormDefinition $form, array $payload = [], int $secondsFilling = 10)
    {
        return $this->post(route('formularz.store', $form->slug), array_merge([
            SpamGuard::TOKEN_FIELD  => $this->token(8, $secondsFilling),
            SpamGuard::ANSWER_FIELD => '8',
            'data'                  => ['imie' => 'Anna', 'tresc' => 'Dzień dobry, mam pytanie.'],
        ], $payload));
    }

    public function test_poprawne_zgloszenie_zostaje_zapisane(): void
    {
        $form = $this->form();

        $this->send($form)->assertRedirect();

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_wypelniony_honeypot_udaje_sukces_i_nie_zapisuje(): void
    {
        $form = $this->form();

        $this->send($form, [SpamGuard::HONEYPOT_FIELD => 'https://spam.example'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_brak_zetonu_blokuje_zgloszenie(): void
    {
        $form = $this->form();

        $this->post(route('formularz.store', $form->slug), [
            'data' => ['imie' => 'Bot', 'tresc' => 'Treść.'],
        ])->assertRedirect();

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_podmieniony_zeton_blokuje_zgloszenie(): void
    {
        $form = $this->form();

        $this->send($form, [SpamGuard::TOKEN_FIELD => 'podrobiony-token'])->assertRedirect();

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_natychmiastowe_wyslanie_blokuje_zgloszenie(): void
    {
        $form = $this->form();

        $this->send($form, secondsFilling: 0)->assertRedirect();

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_przedawniony_formularz_blokuje_zgloszenie(): void
    {
        $form = $this->form();

        $this->send($form, secondsFilling: 3 * 86400)->assertRedirect();

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_bledna_odpowiedz_na_zadanie_daje_czytelny_blad(): void
    {
        $form = $this->form();

        $this->send($form, [SpamGuard::ANSWER_FIELD => '3'])->assertSessionHasErrors('spam');

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_odpowiedz_slowna_jest_przyjmowana(): void
    {
        $form = $this->form();

        $this->send($form, [SpamGuard::ANSWER_FIELD => 'Osiem'])->assertSessionHasNoErrors();

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_brak_odpowiedzi_daje_czytelny_blad(): void
    {
        $form = $this->form();

        $this->send($form, [SpamGuard::ANSWER_FIELD => ''])->assertSessionHasErrors('spam');

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_nadmiar_odnosnikow_daje_czytelny_blad(): void
    {
        $form = $this->form();

        $this->send($form, ['data' => [
            'imie'  => 'Bot',
            'tresc' => 'https://a.example https://b.example https://c.example',
        ]])->assertSessionHasErrors('spam');

        $this->assertSame(0, FormSubmission::count());
    }

    public function test_duplikat_w_krotkim_okienku_daje_czytelny_blad(): void
    {
        $form = $this->form();

        $this->send($form)->assertSessionHasNoErrors();
        $this->send($form)->assertSessionHasErrors('spam');

        $this->assertSame(1, FormSubmission::count());
    }

    public function test_te_same_dane_po_uplywie_okienka_przechodza(): void
    {
        $form = $this->form();

        $this->send($form)->assertSessionHasNoErrors();

        Carbon::setTestNow(now()->addMinutes(15));

        $this->send($form)->assertSessionHasNoErrors();

        $this->assertSame(2, FormSubmission::count());
    }

    public function test_strona_formularza_zawiera_pola_ochronne(): void
    {
        $form = $this->form();

        $this->get(route('formularz.show', $form->slug))
            ->assertOk()
            ->assertSee('name="' . SpamGuard::HONEYPOT_FIELD . '"', false)
            ->assertSee('name="' . SpamGuard::TOKEN_FIELD . '"', false)
            ->assertSee('name="' . SpamGuard::ANSWER_FIELD . '"', false)
            ->assertSee('Zadanie antyspamowe:');
    }
}
