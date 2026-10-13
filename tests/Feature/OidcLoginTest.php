<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OidcLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->update(['sso_enabled' => true, 'sso_name' => 'Keycloak', 'sso_issuer' => 'https://sso.test/realms/org', 'sso_client_id' => 'cms', 'sso_client_secret' => 'tajne']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        Http::fake([
            'sso.test/realms/org/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://sso.test/auth', 'token_endpoint' => 'https://sso.test/token', 'userinfo_endpoint' => 'https://sso.test/userinfo',
            ]),
            'sso.test/token' => Http::response(['access_token' => 'at-123', 'id_token' => '']),
            'sso.test/userinfo' => Http::response(['sub' => 'kc-42', 'email' => 'Ala@Org.pl', 'email_verified' => true]),
        ]);
    }

    private function startLogin(): string
    {
        $location = $this->get(route('auth.sso.redirect'))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://sso.test/auth?', $location);
        $this->assertStringContainsString('code_challenge_method=S256', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        return $q['state'];
    }

    public function test_existing_user_logs_in_and_gets_linked_by_sub(): void
    {
        $user = User::factory()->create(['email' => 'ala@org.pl', 'role' => User::ROLE_EDITOR]);
        $this->get(route('login'))->assertSee('Zaloguj się przez Keycloak');

        $state = $this->startLogin();
        $this->get(route('auth.sso.callback', ['code' => 'abc', 'state' => $state]))->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('kc-42', $user->fresh()->oidc_sub);
        Http::assertSent(fn ($r) => $r->url() === 'https://sso.test/token' && $r['code_verifier'] !== null && $r['client_secret'] === 'tajne');
    }

    public function test_unknown_account_and_bad_state_are_rejected(): void
    {
        $this->startLogin();
        $this->get(route('auth.sso.callback', ['code' => 'abc', 'state' => 'zly']))->assertRedirect(route('login'));
        $this->assertGuest();

        $state = $this->startLogin(); // brak użytkownika ala@org.pl
        $this->get(route('auth.sso.callback', ['code' => 'abc', 'state' => $state]))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }
}
