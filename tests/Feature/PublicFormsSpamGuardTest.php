<?php

namespace Tests\Feature;

use App\Models\AccessibilityReport;
use App\Models\FederationMembershipApplication;
use App\Models\MeetingSignup;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Antyspam (żeton czasu, zadanie tekstowe) w formularzach, które miały tylko pole-pułapkę albo nic. */
class PublicFormsSpamGuardTest extends TestCase
{
    use RefreshDatabase;

    private function guard(string $answer = '8'): array
    {
        return [
            SpamGuard::TOKEN_FIELD => Crypt::encryptString(json_encode(['t' => now()->subSeconds(10)->timestamp, 'a' => 8])),
            SpamGuard::ANSWER_FIELD => $answer,
        ];
    }

    public function test_zgloszenie_bariery_bot_bez_zetonu_nie_jest_zapisane_a_zla_odpowiedz_daje_komunikat(): void
    {
        Mail::fake();
        $report = ['email' => 'anna@example.com', 'message' => 'Brak etykiety.', 'rodo_consent' => '1'];

        $this->post(route('accessibility.report'), $report)->assertSessionHas('accessibility_reported');
        $this->from(route('accessibility.show'))->post(route('accessibility.report'), $report + $this->guard('5'))->assertSessionHasErrors('spam');
        $this->assertSame(0, AccessibilityReport::count());

        $this->post(route('accessibility.report'), $report + $this->guard('osiem'))->assertSessionHas('accessibility_reported');
        $this->assertSame(1, AccessibilityReport::count());
    }

    public function test_zapis_na_spotkanie_i_rezerwacja_odrzucaja_bota_a_blad_trafia_do_wlasnego_worka(): void
    {
        Mail::fake();
        $person = ['name' => 'Jan', 'email' => 'jan@example.com', 'rodo_consent' => '1'];

        $this->post(route('meeting.signup'), $person)->assertSessionHas('meeting_signed_up');
        $this->post(route('booking.store'), $person)->assertSessionHas('booking_signed_up');
        $this->assertSame(0, MeetingSignup::count());

        $this->from(route('contact.show'))->post(route('meeting.signup'), $person + $this->guard('3'))->assertSessionHasErrors('spam', null, 'meeting');
        $this->from(route('booking.show'))->post(route('booking.store'), $person + $this->guard('3'))->assertSessionHasErrors('spam', null, 'booking');

        $this->post(route('booking.store'), $person + $this->guard())->assertSessionHas('booking_signed_up');
        $this->assertSame(1, MeetingSignup::count());
    }

    public function test_zgloszenie_do_federacji_bez_zetonu_nie_jest_zapisane(): void
    {
        Mail::fake();
        $data = [
            'organization_name' => 'Fundacja Test', 'contact_name' => 'Jan', 'email' => 'jan@example.com', 'privacy' => '1',
            'documents' => [UploadedFile::fake()->create('statut.pdf', 10, 'application/pdf')],
        ];

        $this->post(route('federation.join.submit'), $data)->assertSessionHas('application_sent');
        $this->assertSame(0, FederationMembershipApplication::count());
    }

    public function test_strony_formularzy_maja_zadanie_antyspamowe(): void
    {
        foreach (['/deklaracja-dostepnosci', '/rezerwuj-spotkanie-modul'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('name="'.SpamGuard::ANSWER_FIELD.'"', $html, $url);
            $this->assertStringContainsString('Zadanie antyspamowe:', $html, $url);
        }
    }
}
