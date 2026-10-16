<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Modules\Newsletter\Jobs\SendDelivery;
use Modules\Newsletter\Mail\CampaignMail;
use Modules\Newsletter\Mail\QueueAlertMail;
use Modules\Newsletter\Models\NewsletterCampaign;
use Modules\Newsletter\Models\NewsletterDelivery;
use Modules\Newsletter\Services\QueueHealth;
use Modules\Newsletter\Services\SendWindow;
use Tests\TestCase;

/** Utrzymanie wysyłki: stan kolejki i alerty, ponawianie nieudanych, test z danymi subskrybenta, okno wysyłki. */
class NewsletterOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function campaign(string $status = NewsletterCampaign::STATUS_SENT): NewsletterCampaign
    {
        return NewsletterCampaign::create([
            'site_id' => SiteSetting::current()->id, 'title' => 'Kampania testowa', 'subject' => 'Temat',
            'channels' => ['email'], 'audience' => ['all' => true], 'status' => $status, 'html_body' => '<p>Cześć {{first_name}}</p>',
        ]);
    }

    private function stuckJob(int $minutesAgo): void
    {
        $ts = now()->subMinutes($minutesAgo)->getTimestamp();
        DB::table('jobs')->insert(['queue' => 'newsletter', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $ts, 'created_at' => $ts]);
    }

    public function test_stan_kolejki_wykrywa_utkniete_zadania_i_milczacy_worker(): void
    {
        $this->assertTrue(app(QueueHealth::class)->snapshot()['ok']);

        $this->stuckJob(30);
        $state = app(QueueHealth::class)->snapshot();
        $this->assertFalse($state['ok']);
        $this->assertSame(1, $state['pending']);
        $this->assertCount(2, $state['problems']); // utknięte zadanie + brak tętna workera

        QueueHealth::beat();
        $this->assertCount(1, app(QueueHealth::class)->snapshot()['problems']);
    }

    public function test_zadania_odlozone_celowo_nie_sa_alarmem(): void
    {
        $future = now()->addHours(10)->getTimestamp();
        DB::table('jobs')->insert(['queue' => 'newsletter', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => $future, 'created_at' => now()->subHour()->getTimestamp()]);

        $state = app(QueueHealth::class)->snapshot();
        $this->assertTrue($state['ok']);
        $this->assertSame(1, $state['pending']);
    }

    public function test_alert_mailowy_raz_na_godzine_i_baner_na_pulpicie(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $this->stuckJob(30);

        $this->artisan('newsletter:queue-health')->assertSuccessful();
        Mail::assertSent(QueueAlertMail::class, fn ($m) => $m->hasTo($admin->email));

        $this->artisan('newsletter:queue-health')->assertSuccessful();
        Mail::assertSent(QueueAlertMail::class, 1); // drugi alert o tym samym problemie dopiero po godzinie

        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))->assertOk()
            ->assertSee('Wysyłki mogą stać')
            ->assertSee('Worker ostatnio pracował');
    }

    public function test_ponowienie_nieudanych_dostaw(): void
    {
        Queue::fake();
        $admin = $this->admin();
        $campaign = $this->campaign();
        $a = Subscriber::create(['email' => 'a@example.com', 'status' => Subscriber::STATUS_CONFIRMED]);
        $b = Subscriber::create(['email' => 'b@example.com', 'status' => Subscriber::STATUS_CONFIRMED]);
        $failed = NewsletterDelivery::create(['campaign_id' => $campaign->id, 'subscriber_id' => $a->id, 'channel' => 'email', 'status' => 'failed', 'attempts' => 3, 'error_message' => 'Connection timed out']);
        NewsletterDelivery::create(['campaign_id' => $campaign->id, 'subscriber_id' => $b->id, 'channel' => 'email', 'status' => 'hard_bounced', 'error_message' => '550 No such user']);

        $this->actingAs($admin)->get(route('admin.newsletter.kampanie.show', $campaign))->assertOk()
            ->assertSee('Błędy wysyłki')->assertSee('Connection timed out')->assertSee('Ponów nieudane (1)');

        $this->actingAs($admin)->post(route('admin.newsletter.kampanie.retry', $campaign))->assertRedirect();

        $this->assertSame('queued', $failed->fresh()->status);
        $this->assertSame(0, $failed->fresh()->attempts);
        $this->assertSame('hard_bounced', NewsletterDelivery::where('subscriber_id', $b->id)->value('status')); // odbić nie ponawiamy
        Queue::assertPushed(SendDelivery::class, 1);
    }

    public function test_wysylka_testowa_z_danymi_subskrybenta(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $campaign = $this->campaign(NewsletterCampaign::STATUS_DRAFT);
        $real = Subscriber::create(['email' => 'anna@example.com', 'name' => 'Anna Nowak', 'status' => Subscriber::STATUS_CONFIRMED]);

        $this->actingAs($admin)->post(route('admin.newsletter.kampanie.test', $campaign), ['emails' => 'qa@example.com', 'as_subscriber' => 'Anna@example.com'])
            ->assertRedirect()->assertSessionHas('status');

        Mail::assertSent(CampaignMail::class, function (CampaignMail $m) use ($real) {
            return $m->hasTo('qa@example.com') && str_contains($m->message->html, 'Cześć Anna') && ! str_contains($m->message->html, $real->token);
        });

        $this->actingAs($admin)->post(route('admin.newsletter.kampanie.test', $campaign), ['emails' => 'qa@example.com', 'as_subscriber' => 'nikt@example.com'])
            ->assertSessionHas('error');
    }

    public function test_okno_wysylki(): void
    {
        $w = new SendWindow(SendWindow::normalize(['enabled' => true, 'days' => [1, 2, 3, 4, 5], 'from' => '08:00', 'to' => '18:00']));

        $this->assertTrue($w->isOpen(Carbon::parse('2026-10-14 10:00'))); // środa
        $this->assertNull($w->nextOpening(Carbon::parse('2026-10-14 10:00')));
        $this->assertSame('2026-10-19 08:00', $w->nextOpening(Carbon::parse('2026-10-16 18:30'))->format('Y-m-d H:i')); // pt wieczór → pon
        $this->assertSame('2026-10-15 08:00', $w->nextOpening(Carbon::parse('2026-10-15 06:00'))->format('Y-m-d H:i'));
        $this->assertTrue((new SendWindow(SendWindow::normalize([])))->isOpen(Carbon::parse('2026-10-18 03:00'))); // wyłączone = zawsze
    }

    public function test_ustawienia_zapisuja_okno_wysylki_i_pokazuja_dns(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.newsletter.ustawienia.edit'))->assertOk()
            ->assertSee('Okno wysyłki')->assertSee('Dostarczalność domeny nadawcy');

        $payload = [
            'newsletter_rate_per_minute' => 60, 'newsletter_doi_ttl_days' => 7, 'newsletter_retention_days' => 365,
            'send_window' => ['enabled' => '1', 'days' => ['1', '3'], 'from' => '09:00', 'to' => '17:00'],
        ];
        $this->actingAs($admin)->put(route('admin.newsletter.ustawienia.update'), $payload)->assertRedirect();

        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $window = SendWindow::fromSettings(SiteSetting::query()->first());
        $this->assertTrue($window->enabled());
        $this->assertSame([1, 3], $window->config['days']);
        $this->assertSame('pon, śr, 09:00–17:00', $window->describe());
    }

    public function test_dostawa_poza_oknem_czeka_zamiast_wychodzic(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00')); // niedziela
        SiteSetting::current()->forceFill(['newsletter_send_window' => json_encode(['enabled' => true, 'days' => [1, 2, 3, 4, 5], 'from' => '08:00', 'to' => '18:00'])])->save();
        $campaign = $this->campaign(NewsletterCampaign::STATUS_SENDING);
        $s = Subscriber::create(['email' => 'c@example.com', 'status' => Subscriber::STATUS_CONFIRMED]);
        $d = NewsletterDelivery::create(['campaign_id' => $campaign->id, 'subscriber_id' => $s->id, 'channel' => 'email', 'status' => 'queued']);

        app()->call([new SendDelivery($d->id), 'handle']);

        Mail::assertNothingSent();
        $this->assertSame('queued', $d->fresh()->status);
        $this->assertSame(0, (int) $d->fresh()->attempts);
    }

    public function test_porownanie_kampanii_i_eksporty(): void
    {
        $admin = $this->admin();
        foreach ([[30, 100, 40, 10, 1], [5, 200, 50, 20, 2]] as $i => [$daysAgo, $delivered, $opened, $clicked, $unsub]) {
            $c = $this->campaign();
            $c->forceFill(['title' => 'Wydanie ' . ($i + 1), 'finished_at' => now()->subDays($daysAgo), 'started_at' => now()->subDays($daysAgo), 'recipients_count' => $delivered,
                'sent_count' => $delivered, 'delivered_count' => $delivered, 'opened_unique' => $opened, 'clicked_unique' => $clicked, 'unsubscribed_count' => $unsub])->save();
        }

        $this->actingAs($admin)->get(route('admin.newsletter.raporty.index'))->assertOk()
            ->assertSee('Wydanie 1')->assertSee('Wydanie 2')
            ->assertSee('30,0 %') // otwarcia razem: 90 / 300
            ->assertSee('Trend miesięczny');

        $csv = $this->actingAs($admin)->get(route('admin.newsletter.raporty.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Wydanie 2";', $csv);
        $this->assertStringContainsString('"RAZEM / ŚREDNIO";;300;300;90;30;30;10;', $csv);
        $this->assertStringNotContainsString('@', $csv); // bez adresów

        $this->actingAs($admin)->get(route('admin.newsletter.raporty.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('admin.newsletter.kampanie.report.pdf', NewsletterCampaign::first()))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_szablony_maja_naglowek_z_logo_i_nazwa_a_edytor_wstawia_go_do_nowego_projektu(): void
    {
        foreach (['feer-1', 'feer-2'] as $tpl) {
            $html = file_get_contents(public_path("vendor/mosaico/templates/{$tpl}/template-{$tpl}.html"));
            $this->assertStringContainsString('data-ko-block="brandHeaderBlock"', $html);
            $this->assertStringContainsString('data-ko-editable="orgName"', $html);
            $this->assertFileExists(public_path("vendor/mosaico/templates/{$tpl}/edres/brandHeaderBlock.png"));
        }

        SiteSetting::current()->update(['site_name' => 'Fundacja Testowa', 'facebook_url' => 'https://facebook.com/fundacjafeer']);
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.newsletter.mosaico'))->assertRedirect();
        $template = \Modules\Newsletter\Models\NewsletterTemplate::first();
        $this->actingAs($admin)->get(route('admin.newsletter.szablony.editor', $template))->assertOk()
            ->assertSee('function insertBrandHeader', false)
            ->assertSee('"name":"Fundacja Testowa"', false)
            ->assertSee('"title":"FEERletter"', false)
            ->assertSee('"fb":"https:\\/\\/facebook.com\\/fundacjafeer"', false)
            ->assertSee('function fillSocial', false);

        $campaign = $this->actingAs($admin)->post(route('admin.newsletter.kampanie.store'), ['title' => 'Nowa', 'subject' => 'Temat', 'channels' => ['email']]);
        $this->assertSame('feer-2', \Modules\Newsletter\Models\NewsletterCampaign::latest('id')->value('mosaico_template'));
    }

    public function test_zajawki_z_cms_bez_encji_html(): void
    {
        $this->assertSame('Bank – główny rachunek', \Modules\Newsletter\Services\ContentFeeder::plain('<p>Bank &ndash; gł&oacute;wny&nbsp;rachunek</p>'));
        $this->assertSame('A B', \Modules\Newsletter\Services\ContentFeeder::plain('<p>A</p><p>B</p>'));
    }
}
