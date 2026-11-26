<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
