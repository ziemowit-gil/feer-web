<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoLogtoCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fresh(): SiteSetting
    {
        return SiteSetting::query()->find(1);
    }

    public function test_saves_client_data_and_enables_sso(): void
    {
        $this->artisan('sso:logto', ['--client-id' => 'app-1', '--client-secret' => 'tajne'])->assertSuccessful();
        $s = $this->fresh();
        $this->assertTrue((bool) $s->sso_enabled);
        $this->assertSame('https://logto.feer.org.pl/oidc', $s->sso_issuer);
        $this->assertSame('app-1', $s->sso_client_id);
        $this->assertSame('tajne', $s->sso_client_secret);
        $this->assertSame('logowanie jednokrotne', $s->sso_name);
        $this->assertFalse((bool) $s->sso_only_login);
    }

    public function test_asks_for_secret_when_missing(): void
    {
        $this->artisan('sso:logto', ['--client-id' => 'app-2'])
            ->expectsQuestion('App secret z panelu Logto', 'z-pytania')->assertSuccessful();
        $this->assertSame('z-pytania', $this->fresh()->sso_client_secret);
    }

    public function test_create_makes_traditional_app_in_logto(): void
    {
        Http::fake([
            'logto.feer.org.pl/oidc/token' => Http::response(['access_token' => 'm2m-at']),
            'logto.feer.org.pl/api/applications' => Http::response(['id' => 'new-app', 'secret' => 'new-secret']),
        ]);
        $this->artisan('sso:logto', ['--create' => true, '--m2m-id' => 'm2m', '--m2m-secret' => 's', '--url' => 'https://feer.org.pl'])->assertSuccessful();

        Http::assertSent(fn ($r) => $r->url() === 'https://logto.feer.org.pl/api/applications'
            && $r['type'] === 'Traditional'
            && $r['oidcClientMetadata']['redirectUris'] === ['https://feer.org.pl/auth/sso/callback']);
        $s = $this->fresh();
        $this->assertSame('new-app', $s->sso_client_id);
        $this->assertSame('new-secret', $s->sso_client_secret);
        $this->assertTrue((bool) $s->sso_enabled);
    }

    public function test_dry_run_and_disable(): void
    {
        $this->artisan('sso:logto', ['--client-id' => 'x', '--client-secret' => 'y', '--dry-run' => true])->assertSuccessful();
        $this->assertNull($this->fresh()?->sso_client_id);

        $this->artisan('sso:logto', ['--client-id' => 'x', '--client-secret' => 'y', '--only' => true])->assertSuccessful();
        $this->assertTrue((bool) $this->fresh()->sso_only_login);
        $this->artisan('sso:logto', ['--disable' => true])->assertSuccessful();
        $this->assertFalse((bool) $this->fresh()->sso_enabled);
    }

    public function test_without_options_prints_instructions(): void
    {
        $this->artisan('sso:logto')->expectsOutputToContain('/auth/sso/callback')->assertSuccessful();
    }
}
