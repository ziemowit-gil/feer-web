<?php

namespace Tests\Feature\Admin;

use App\Mail\MeetingSignupMail;
use App\Mail\ScheduleChangeMail;
use App\Models\MeetingSignup;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMeetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the static singleton cache so a stale model from a rolled-back
        // row can't leak between tests (see SiteSetting::current()).
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'FEER',
            'brand_color' => '#c31432',
            'header_layout' => 'classic',
            'content_editor' => 'tinymce',
            'mail_transport' => 'default',
            'contact_address' => 'Barbackiego 28/18',
            'contact_city' => '33-300 Nowy Sącz',
            'contact_email' => 'kontakt@feer.org.pl',
        ], $overrides);
    }

    public function test_rsvp_is_stored_and_emailed_to_the_notify_address(): void
    {
        Mail::fake();

        SiteSetting::current()->update([
            'contact_email' => 'kontakt@feer.org.pl',
            'contact_meeting_notify_email' => 'spotkania@feer.org.pl',
        ]);

        $this->post(route('meeting.signup'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'term' => '15 sierpnia 2026 — Kraków, Rynek 1',
            'message' => 'Będę z osobą towarzyszącą.',
            'rodo_consent' => '1',
            'website' => '',
        ])->assertRedirect(route('contact.show').'#spotkania');

        $this->assertDatabaseHas('meeting_signups', [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'term' => '15 sierpnia 2026 — Kraków, Rynek 1',
        ]);

        Mail::assertSent(MeetingSignupMail::class, function ($mail) {
            return $mail->hasTo('spotkania@feer.org.pl');
        });
    }

    public function test_rsvp_requires_consent_and_rejects_honeypot(): void
    {
        Mail::fake();

        // Brak zgody RODO → błąd walidacji (nazwany bag „meeting”), nic nie zapisane.
        $this->from(route('contact.show'))->post(route('meeting.signup'), [
            'name' => 'Anna',
            'email' => 'anna@example.com',
        ])->assertSessionHasErrors('rodo_consent', null, 'meeting');

        // Wypełniony honeypot → odrzucone.
        $this->from(route('contact.show'))->post(route('meeting.signup'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'rodo_consent' => '1',
            'website' => 'spam',
        ])->assertSessionHasErrors('website', null, 'meeting');

        $this->assertDatabaseCount('meeting_signups', 0);
    }

    public function test_schedule_normalization_keeps_valid_rows_only(): void
    {
        $this->actingAs($this->admin())->put(route('admin.ustawienia.update'), $this->payload([
            'contact_schedule' => [
                ['type' => 'date', 'date' => '2026-08-15', 'time' => '10:00', 'where' => 'Kraków', 'note' => ''],
                ['type' => 'weekly', 'weekday' => '3', 'time' => '', 'where' => 'Biblioteka', 'note' => ''],
                ['type' => 'date', 'date' => '', 'where' => 'brak daty — do odrzucenia'],
                ['type' => 'weekly', 'weekday' => '', 'where' => 'brak dnia — do odrzucenia'],
            ],
        ]))->assertRedirect(route('admin.ustawienia.edit'));

        $schedule = SiteSetting::query()->first()->contact_schedule;

        $this->assertCount(2, $schedule);
        $this->assertSame('date', $schedule[0]['type']);
        $this->assertSame('2026-08-15', $schedule[0]['date']);
        $this->assertSame('weekly', $schedule[1]['type']);
        $this->assertSame(3, $schedule[1]['weekday']);
    }

    public function test_schedule_upcoming_hides_past_and_flags_nearest(): void
    {
        $settings = SiteSetting::current();
        $settings->update([
            'contact_schedule' => [
                ['type' => 'date', 'date' => '2000-01-01', 'time' => '', 'where' => 'przeszłe', 'note' => ''],
                ['type' => 'date', 'date' => now()->addDays(30)->toDateString(), 'time' => '', 'where' => 'dalej', 'note' => ''],
                ['type' => 'date', 'date' => now()->addDays(5)->toDateString(), 'time' => '', 'where' => 'blisko', 'note' => ''],
            ],
        ]);

        $upcoming = $settings->contactScheduleUpcoming();

        $this->assertCount(2, $upcoming);            // przeszły termin ukryty
        $this->assertTrue($upcoming[0]['is_next']);  // najbliższy pierwszy
        $this->assertSame('blisko', $upcoming[0]['where']);
        $this->assertFalse($upcoming[1]['is_next']);
    }

    public function test_schedule_change_notifies_signups_with_admin_copy(): void
    {
        Mail::fake();

        MeetingSignup::create(['name' => 'A', 'email' => 'a@example.com']);
        MeetingSignup::create(['name' => 'B', 'email' => 'b@example.com']);

        $this->actingAs($this->admin())->put(route('admin.ustawienia.update'), $this->payload([
            'contact_meeting_notify_email' => 'spotkania@feer.org.pl',
            'contact_schedule' => [
                ['type' => 'date', 'date' => now()->addDays(7)->toDateString(), 'time' => '10:00', 'where' => 'Kraków', 'note' => ''],
            ],
            'notify_schedule_change' => '1',
        ]))->assertRedirect(route('admin.ustawienia.edit'));

        Mail::assertSent(ScheduleChangeMail::class, function ($mail) {
            return $mail->hasTo('spotkania@feer.org.pl')
                && $mail->hasBcc('a@example.com')
                && $mail->hasBcc('b@example.com');
        });
    }

    public function test_schedule_change_not_sent_when_checkbox_off(): void
    {
        Mail::fake();

        MeetingSignup::create(['name' => 'A', 'email' => 'a@example.com']);

        $this->actingAs($this->admin())->put(route('admin.ustawienia.update'), $this->payload([
            'contact_schedule' => [
                ['type' => 'date', 'date' => now()->addDays(7)->toDateString(), 'where' => 'Kraków'],
            ],
        ]))->assertRedirect(route('admin.ustawienia.edit'));

        Mail::assertNothingSent();
    }

    public function test_contact_page_shows_meeting_section(): void
    {
        SiteSetting::current()->update([
            'contact_remote_note' => 'Na co dzień działamy zdalnie.',
            'contact_online_meeting_url' => 'https://calendly.com/feer',
            'contact_schedule' => [
                ['type' => 'date', 'date' => now()->addDays(10)->toDateString(), 'time' => '10:00–14:00', 'where' => 'Kraków, Rynek 1', 'note' => ''],
            ],
        ]);

        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertSee('Na co dzień działamy zdalnie.');
        $response->assertSee('Zapraszamy na spotkanie online');
        $response->assertSee('Najbliższy termin');
        $response->assertSee('Daj znać, że przyjdziesz');
        $response->assertSee('Kraków, Rynek 1');
    }
}
