<?php

namespace Tests\Feature;

use App\Mail\PageDeadlineReminderMail;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PageDeadlineRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        Carbon::setTestNow('2026-10-11 08:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function make(string $type, string $slug, array $typeData, array $attrs = []): Page
    {
        return Page::create($attrs + [
            'title' => 'Strona '.$slug,
            'slug' => $slug,
            'type' => $type,
            'is_published' => true,
            'type_data' => $typeData,
        ]);
    }

    private function sentFor(Page $page): \Illuminate\Support\Collection
    {
        return Mail::sent(PageDeadlineReminderMail::class, fn ($m) => $m->page->is($page));
    }

    public function test_recruitment_ending_in_three_days_notifies_author_once(): void
    {
        $author = User::factory()->create(['email' => 'autorka@example.org']);
        $page = $this->make('recruitment', 'nabor', ['rec_ends_at' => '2026-10-14', 'contact_email' => 'kontakt@example.org'], ['created_by' => $author->id]);
        $later = $this->make('recruitment', 'nabor-pozniej', ['rec_ends_at' => '2026-10-20', 'contact_email' => 'kontakt@example.org']);

        $this->artisan('strony:przypomnienia')->assertSuccessful();

        Mail::assertSent(PageDeadlineReminderMail::class, fn ($m) => $m->page->is($page)
            && $m->kind === 'rec_end_soon'
            && $m->hasTo('autorka@example.org')
            && $m->headline === 'Nabór kończy się za 3 dni'
            && str_contains($m->editUrl, '/podstrony/'.$page->id.'/edit'));
        $this->assertCount(0, $this->sentFor($later));
        $this->assertContains('rec_end_soon:2026-10-14', $page->fresh()->type_data['reminders_sent']);

        // Kolejne dni: bez ponownej wysyłki.
        Carbon::setTestNow('2026-10-12 08:00:00');
        $this->artisan('strony:przypomnienia')->assertSuccessful();
        Mail::assertSentCount(1);

        // Zmiana terminu = nowe przypomnienie.
        $page->refresh();
        $page->update(['type_data' => ['rec_ends_at' => '2026-10-15'] + $page->type_data]);
        $this->artisan('strony:przypomnienia')->assertSuccessful();
        Mail::assertSentCount(2);
    }

    public function test_falls_back_to_contact_email_and_skips_without_recipient(): void
    {
        $withContact = $this->make('tender', 'zapytanie', ['tender_deadline' => '2026-10-13T10:00', 'contact_email' => 'zamowienia@example.org']);
        $noRecipient = $this->make('tender', 'zapytanie-bez', ['tender_deadline' => '2026-10-12']);

        $this->artisan('strony:przypomnienia')->assertSuccessful();

        Mail::assertSent(PageDeadlineReminderMail::class, fn ($m) => $m->page->is($withContact)
            && $m->kind === 'tender_deadline_soon'
            && $m->hasTo('zamowienia@example.org')
            && $m->headline === 'Termin składania ofert mija za 2 dni');
        $this->assertCount(0, $this->sentFor($noRecipient));
        $this->assertArrayNotHasKey('reminders_sent', $noRecipient->fresh()->type_data);
    }

    public function test_closed_recruitment_without_results_info_is_reminded_once(): void
    {
        $page = $this->make('recruitment', 'nabor', ['rec_ends_at' => '2026-10-05', 'contact_email' => 'kontakt@example.org']);
        $withResults = $this->make('recruitment', 'nabor-z-info', ['rec_ends_at' => '2026-10-05', 'rec_results' => 'Wyniki ogłosimy 20 października.', 'contact_email' => 'kontakt@example.org']);
        $ancient = $this->make('recruitment', 'nabor-dawny', ['rec_ends_at' => '2025-01-31', 'contact_email' => 'kontakt@example.org']);

        $this->artisan('strony:przypomnienia')->assertSuccessful();
        $this->artisan('strony:przypomnienia')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(PageDeadlineReminderMail::class, fn ($m) => $m->page->is($page)
            && $m->kind === 'rec_results' && $m->headline === 'Uzupełnij informację po zakończeniu naboru');
        $this->assertCount(0, $this->sentFor($withResults));
        $this->assertCount(0, $this->sentFor($ancient));
    }

    public function test_tender_in_evaluation_a_week_after_deadline_asks_for_result(): void
    {
        $page = $this->make('tender', 'zapytanie', ['tender_deadline' => '2026-10-01', 'contact_email' => 'zamowienia@example.org']);
        $recent = $this->make('tender', 'zapytanie-swieze', ['tender_deadline' => '2026-10-08', 'contact_email' => 'zamowienia@example.org']);
        $awarded = $this->make('tender', 'zapytanie-rozstrzygniete', ['tender_deadline' => '2026-10-01', 'tender_result' => 'Wybrano ofertę X.', 'contact_email' => 'zamowienia@example.org']);

        $this->artisan('strony:przypomnienia')->assertSuccessful();
        $this->artisan('strony:przypomnienia')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(PageDeadlineReminderMail::class, fn ($m) => $m->page->is($page) && $m->kind === 'tender_result');
        $this->assertCount(0, $this->sentFor($recent));
        $this->assertCount(0, $this->sentFor($awarded));
    }

    public function test_unpublished_pages_are_ignored_and_reminders_sent_survives_type_data(): void
    {
        $this->make('recruitment', 'szkic', ['rec_ends_at' => '2026-10-12', 'contact_email' => 'kontakt@example.org'], ['is_published' => false]);
        $page = $this->make('recruitment', 'nabor', ['rec_ends_at' => '2026-10-12', 'contact_email' => 'kontakt@example.org']);

        $this->artisan('strony:przypomnienia')->assertSuccessful();

        Mail::assertSentCount(1);
        $this->assertSame(['rec_end_soon:2026-10-12'], $page->fresh()->typeData()['reminders_sent']);
    }

    public function test_mail_renders(): void
    {
        $page = $this->make('recruitment', 'nabor', ['rec_ends_at' => '2026-10-11']);
        $mail = new PageDeadlineReminderMail($page, 'rec_end_soon', 'Nabór kończy się dziś', 'Treść.', 'http://localhost/admin/podstrony/1/edit', 'http://localhost/nabor');

        $mail->assertSeeInHtml('Nabór kończy się dziś');
        $mail->assertSeeInHtml('Edytuj stronę w panelu');
        $mail->assertHasSubject('Nabór kończy się dziś: Strona nabor');
    }
}
