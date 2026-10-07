<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactSpamGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    /** @param array<string, mixed> $payload */
    private function send(array $payload = [], int $secondsAgo = 10)
    {
        return $this->post(route('contact.store'), array_merge([
            'name'                  => 'Anna Kowalska',
            'email'                 => 'anna@example.com',
            'message'               => 'Dzień dobry, mam pytanie o projekt.',
            'rodo_consent'          => '1',
            SpamGuard::TOKEN_FIELD  => Crypt::encryptString(json_encode(['t' => now()->subSeconds($secondsAgo)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => 'osiem',
        ], $payload));
    }

    public function test_poprawna_wiadomosc_zostaje_zapisana(): void
    {
        $this->send()->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
    }

    public function test_zla_odpowiedz_na_zadanie_jest_odrzucona_z_komunikatem(): void
    {
        $this->send([SpamGuard::ANSWER_FIELD => '7'])->assertSessionHasErrors('spam');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_zbyt_szybka_wysylka_jest_cicho_odrzucona(): void
    {
        $this->send([], 0)->assertSessionHas('status')->assertSessionHasNoErrors();

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_brak_zetonu_jest_cicho_odrzucony(): void
    {
        $this->send([SpamGuard::TOKEN_FIELD => ''])->assertSessionHas('status');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_nadmiar_odnosnikow_jest_odrzucony(): void
    {
        $this->send(['message' => 'http://a.pl http://b.pl http://c.pl'])->assertSessionHasErrors('spam');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_formularz_zawiera_zadanie_antyspamowe(): void
    {
        $html = view('contact.partials.form', ['coordinators' => collect(), 'siteSettings' => SiteSetting::current(), 'errors' => new \Illuminate\Support\ViewErrorBag])->render();

        $this->assertStringContainsString('Zadanie antyspamowe', $html);
        $this->assertStringContainsString(SpamGuard::TOKEN_FIELD, $html);
    }
}
