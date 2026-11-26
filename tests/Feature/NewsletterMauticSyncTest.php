<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Modules\Newsletter\Jobs\SendDoubleOptIn;
use Modules\Newsletter\Mail\DoubleOptInMail;
use Modules\Newsletter\Services\CrmSync;
use Tests\TestCase;

/** Newsletter → Mautic: kontakt i segment po potwierdzeniu, blokada przy wypisie, usunięcie przy anonimizacji, ustawienia. */
class NewsletterMauticSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function enableMautic(): SiteSetting
    {
        $site = SiteSetting::current();
        $site->forceFill([
            'newsletter_mautic_sync' => true,
            'newsletter_mautic_url' => 'https://marketing.example.test/',
            'newsletter_mautic_username' => 'api-www',
            'newsletter_mautic_password' => 'tajne',
            'newsletter_mautic_segment' => 8,
        ])->save();

        return $site;
    }

    private function subscriber(string $status = Subscriber::STATUS_CONFIRMED): Subscriber
    {
        return Subscriber::create([
            'email' => 'ala@example.com', 'name' => 'Ala Nowak', 'site_id' => SiteSetting::current()->id,
            'status' => $status, 'topics' => ['news'], 'channels' => ['email'], 'source' => 'test',
            'confirmed_at' => $status === Subscriber::STATUS_CONFIRMED ? now() : null,
        ]);
    }

    public function test_confirmed_subscriber_is_created_in_mautic_and_added_to_segment(): void
    {
        $this->enableMautic();
        Http::fake([
            'marketing.example.test/api/contacts/new' => Http::response(['contact' => ['id' => 42]], 201),
            'marketing.example.test/api/segments/8/contact/42/add' => Http::response(['success' => 1]),
            'marketing.example.test/api/contacts/42/dnc/email/remove' => Http::response(['contact' => ['id' => 42]]),
        ]);
        $s = $this->subscriber();

        app(CrmSync::class)->sync($s, 'confirmed');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://marketing.example.test/api/contacts/new'
            && $r['email'] === 'ala@example.com'
            && $r['firstname'] === 'Ala Nowak'
            && in_array('zapis-ze-strony', $r['tags'], true)
            && in_array('temat-news', $r['tags'], true)
            && $r->hasHeader('Authorization', 'Basic ' . base64_encode('api-www:tajne')));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/segments/8/contact/42/add'));
        $s->refresh();
        $this->assertSame(42, (int) $s->mautic_contact_id);
        $this->assertNotNull($s->mautic_synced_at);
        $this->assertNull($s->mautic_error);
    }

    public function test_pending_subscriber_is_not_sent(): void
    {
        $this->enableMautic();
        Http::fake();

        app(CrmSync::class)->sync($this->subscriber(Subscriber::STATUS_PENDING), 'confirmed');

        Http::assertNothingSent();
    }

    public function test_unsubscribe_adds_do_not_contact(): void
    {
        $this->enableMautic();
        Http::fake(['marketing.example.test/api/contacts/42/dnc/email/add' => Http::response(['contact' => ['id' => 42]])]);
        $s = $this->subscriber(Subscriber::STATUS_UNSUBSCRIBED);
        $s->forceFill(['mautic_contact_id' => 42])->save();

        app(CrmSync::class)->sync($s, 'unsubscribed');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/contacts/42/dnc/email/add') && $r['reason'] === 3);
    }

    public function test_anonymize_deletes_contact(): void
    {
        $this->enableMautic();
        Http::fake(['marketing.example.test/api/contacts/42/delete' => Http::response(['contact' => []])]);
        $s = $this->subscriber();
        $s->forceFill(['mautic_contact_id' => 42])->save();

        app(CrmSync::class)->sync($s, 'anonymized');

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/api/contacts/42/delete'));
        $this->assertNull($s->refresh()->mautic_contact_id);
    }

    public function test_api_error_is_stored_not_thrown(): void
    {
        $this->enableMautic();
        Http::fake(['marketing.example.test/*' => Http::response(['errors' => []], 401)]);
        $s = $this->subscriber();

        app(CrmSync::class)->sync($s, 'confirmed');

        $this->assertStringContainsString('HTTP 401', (string) $s->refresh()->mautic_error);
    }

    public function test_disabled_integration_sends_nothing(): void
    {
        Http::fake();

        app(CrmSync::class)->sync($this->subscriber(), 'confirmed');

        Http::assertNothingSent();
    }

    private function pendingSubscriber(): Subscriber
    {
        $s = $this->subscriber(Subscriber::STATUS_PENDING);
        $s->forceFill(['token' => 'tok123'])->save();

        return $s;
    }

    public function test_confirmation_email_is_sent_by_mautic_with_cms_confirm_link(): void
    {
        $this->enableMautic()->forceFill(['newsletter_mautic_confirm_email' => 5])->save();
        Mail::fake();
        Http::fake([
            'marketing.example.test/api/contacts/new' => Http::response(['contact' => ['id' => 42]], 201),
            'marketing.example.test/api/emails/5/contact/42/send' => Http::response(['success' => 1]),
        ]);
        $s = $this->pendingSubscriber();

        (new SendDoubleOptIn($s->id))->handle();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/contacts/new') && in_array('newsletter-do-potwierdzenia', $r['tags'], true));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/emails/5/contact/42/send')
            && $r['tokens']['{confirm_url}'] === route('newsletter.confirm', 'tok123'));
        Mail::assertNothingSent();
        $this->assertNotNull($s->refresh()->confirmation_sent_at);
    }

    public function test_confirmation_falls_back_to_cms_mail_when_mautic_fails(): void
    {
        $this->enableMautic()->forceFill(['newsletter_mautic_confirm_email' => 5])->save();
        Mail::fake();
        Http::fake(['marketing.example.test/*' => Http::response([], 500)]);
        $s = $this->pendingSubscriber();

        (new SendDoubleOptIn($s->id))->handle();

        Mail::assertSent(DoubleOptInMail::class);
        $this->assertNotNull($s->refresh()->mautic_error);
    }

    public function test_confirmed_sync_removes_pending_tag(): void
    {
        $this->enableMautic();
        Http::fake([
            'marketing.example.test/api/contacts/new' => Http::response(['contact' => ['id' => 42]], 201),
            'marketing.example.test/*' => Http::response(['success' => 1]),
        ]);

        app(CrmSync::class)->sync($this->subscriber(), 'confirmed');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/api/contacts/new') && in_array('-newsletter-do-potwierdzenia', $r['tags'], true));
    }

    public function test_mautic_webhook_unsubscribes_after_checking_api(): void
    {
        $this->enableMautic();
        Http::fake(['marketing.example.test/api/contacts/42' => Http::response(['contact' => [
            'id' => 42, 'fields' => ['all' => ['email' => 'ala@example.com']],
            'doNotContact' => [['channel' => 'email', 'reason' => 1, 'comments' => 'Wypisany z maila']],
        ]])]);
        $s = $this->subscriber();

        $this->postJson(route('newsletter.webhook.mautic'), ['mautic.lead_channel_subscription_changed' => [['contact' => ['id' => 42], 'channel' => 'email', 'new_status' => 'unsubscribed']]])
            ->assertOk()->assertJson(['changed' => 1]);

        $this->assertSame(Subscriber::STATUS_UNSUBSCRIBED, $s->refresh()->status);
    }

    public function test_mautic_webhook_bounce_and_ignores_contacts_without_dnc(): void
    {
        $this->enableMautic();
        Http::fake([
            'marketing.example.test/api/contacts/42' => Http::response(['contact' => ['fields' => ['all' => ['email' => 'ala@example.com']], 'doNotContact' => [['channel' => 'email', 'reason' => 2]]]]),
            'marketing.example.test/api/contacts/43' => Http::response(['contact' => ['fields' => ['all' => ['email' => 'ala@example.com']], 'doNotContact' => []]]),
        ]);
        $s = $this->subscriber();

        $this->postJson(route('newsletter.webhook.mautic'), ['mautic.lead_channel_subscription_changed' => [['contact' => ['id' => 43]]]])->assertJson(['changed' => 0]);
        $this->assertSame(Subscriber::STATUS_CONFIRMED, $s->refresh()->status);

        $this->postJson(route('newsletter.webhook.mautic'), ['mautic.lead_channel_subscription_changed' => [['contact' => ['id' => 42]]]])->assertJson(['changed' => 1]);
        $this->assertSame(Subscriber::STATUS_BOUNCED, $s->refresh()->status);
    }

    public function test_mautic_webhook_does_nothing_when_integration_off(): void
    {
        Http::fake();

        $this->postJson(route('newsletter.webhook.mautic'), ['mautic.lead_channel_subscription_changed' => [['contact' => ['id' => 42]]]])->assertJson(['changed' => 0]);

        Http::assertNothingSent();
    }

    public function test_campaign_creation_is_blocked_when_mautic_sends(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->enableMautic()->forceFill(['newsletter_mautic_campaigns' => true])->save();

        $this->actingAs($admin)->get(route('admin.newsletter.kampanie.create'))->assertRedirect(route('admin.newsletter.dashboard'));
        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))->assertOk()
            ->assertSee('Kampanie wysyłasz w Mautic')
            ->assertDontSee(route('admin.newsletter.kampanie.create'))
            ->assertDontSee(route('admin.newsletter.mosaico'));
        $this->actingAs($admin)->get(route('admin.newsletter.kampanie.index'))->assertOk();
    }

    public function test_campaign_creation_works_normally_without_mautic_flag(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->enableMautic();

        $this->actingAs($admin)->get(route('admin.newsletter.kampanie.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.newsletter.dashboard'))->assertOk()->assertDontSee('Kampanie wysyłasz w Mautic');
    }

    public function test_settings_save_keeps_password_when_blank_and_works_without_columns(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->enableMautic();

        $base = [
            'newsletter_rate_per_minute' => 60, 'newsletter_doi_ttl_days' => 7, 'newsletter_retention_days' => 365,
            'newsletter_mautic_sync' => '1', 'newsletter_mautic_url' => 'https://marketing.feer.org.pl',
            'newsletter_mautic_username' => 'api-feer-www', 'newsletter_mautic_password' => '', 'newsletter_mautic_segment' => 9,
        ];
        $this->actingAs($admin)->put(route('admin.newsletter.ustawienia.update'), $base)->assertSessionHasNoErrors();
        $site = SiteSetting::query()->find(SiteSetting::current()->id);
        $this->assertSame('tajne', $site->newsletter_mautic_password);
        $this->assertSame(9, (int) $site->newsletter_mautic_segment);
        $this->assertSame('api-feer-www', $site->newsletter_mautic_username);

        // Przed migracją (brak kolumn) zapis ustawień nie może paść.
        Schema::table('site_settings', function ($t) {
            $t->dropColumn(['newsletter_mautic_sync', 'newsletter_mautic_url', 'newsletter_mautic_username', 'newsletter_mautic_password', 'newsletter_mautic_segment']);
        });
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->actingAs($admin)->put(route('admin.newsletter.ustawienia.update'), $base)->assertRedirect();
        $this->actingAs($admin)->get(route('admin.newsletter.ustawienia.edit'))->assertOk()->assertSee('wymaga migracji');
    }
}
