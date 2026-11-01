<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        Page::create(['title' => 'Start', 'slug' => 'start', 'type' => 'standard', 'is_published' => true]);
    }

    public function test_no_tracking_code_without_configuration(): void
    {
        $this->get('/start')->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('matomo.js', false);
    }

    public function test_google_analytics_snippet_rendered(): void
    {
        SiteSetting::current()->update(['ga_measurement_id' => 'G-TEST123']);

        $this->get('/start')->assertOk()
            ->assertSee('"G-TEST123"', false)
            ->assertSee('feer:cookie-consent', false);
    }

    public function test_matomo_snippet_rendered_with_normalized_url(): void
    {
        SiteSetting::current()->update([
            'matomo_url' => 'https://stats.example.org',
            'matomo_site_id' => '7',
            'matomo_cookieless' => true,
        ]);

        $this->get('/start')->assertOk()
            ->assertSee('https:\/\/stats.example.org\/', false)
            ->assertSee('"site_id":"7"', false)
            ->assertSee('"cookieless":true', false)
            ->assertSee("'disableCookies'", false);
    }

    public function test_clarity_snippet_rendered_after_consent_signal(): void
    {
        SiteSetting::current()->update(['clarity_project_id' => 'abcd1234ef']);

        $this->get('/start')->assertOk()
            ->assertSee('"abcd1234ef"', false)
            ->assertSee('https://www.clarity.ms/tag/', false)
            ->assertSee('feer:cookie-consent', false);
    }

    public function test_clarity_project_id_is_validated_and_saved(): void
    {
        $this->assertSame('abcd1234ef', tap(SiteSetting::current())->update(['clarity_project_id' => 'abcd1234ef'])->fresh()->clarity_project_id);
    }

    public function test_matomo_config_requires_both_fields(): void
    {
        $settings = SiteSetting::current();
        $settings->update(['matomo_url' => 'https://stats.example.org/', 'matomo_site_id' => null]);

        $this->assertNull($settings->fresh()->matomoConfig());
    }
}
