<?php

namespace Tests\Feature;

use App\Models\CookieConsent;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_banner_has_equal_accept_and_reject_buttons_and_footer_settings_link(): void
    {
        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
        SiteSetting::current()->update(['cookie_banner_enabled' => true]);

        $this->get('/start')->assertOk()
            ->assertSee('data-cookie-accept', false)
            ->assertSee('data-cookie-reject', false)
            ->assertSee('Odrzucam')
            ->assertSee('data-cookie-settings', false)
            ->assertSee('Ustawienia cookies');
    }

    public function test_decision_is_logged_with_anonymized_ip_without_csrf(): void
    {
        $id = '3f1c2b9e-8a4d-4c1e-9b7a-2d5e6f708192';

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.57'])
            ->postJson('/cookie-consent', ['consent_id' => $id, 'decision' => 'rejected'])
            ->assertNoContent();

        $this->assertDatabaseHas('cookie_consents', [
            'consent_id' => $id,
            'decision' => 'rejected',
            'ip_anon' => '203.0.113.0',
            'site_id' => SiteSetting::current()->id,
        ]);
    }

    public function test_invalid_decision_is_rejected(): void
    {
        $this->postJson('/cookie-consent', ['consent_id' => 'nie-uuid', 'decision' => 'maybe'])
            ->assertStatus(422);

        $this->assertSame(0, CookieConsent::count());
    }

    public function test_ipv6_is_truncated_to_48_bits(): void
    {
        $this->assertSame('2001:db8:85a3::', CookieConsent::anonymizeIp('2001:db8:85a3:8d3:1319:8a2e:370:7348'));
    }
}
