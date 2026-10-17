<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/** Strona „Potwierdź tożsamość”: polskie komunikaty i potwierdzenie przez Microsoft 365 zamiast hasła. */
class ConfirmIdentityMicrosoftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function enableMicrosoft(): void
    {
        SiteSetting::current()->update(['microsoft_login_enabled' => true, 'microsoft_client_id' => 'x', 'microsoft_client_secret' => 'y', 'microsoft_tenant_id' => 'z']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function fakeMicrosoftUser(string $id, string $email): void
    {
        $msUser = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'name' => 'Osoba', 'avatar' => null]);
        $provider = \Mockery::mock(\Laravel\Socialite\Two\AbstractProvider::class);
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://login.microsoftonline.com/fake'));
        $provider->shouldReceive('user')->andReturn($msUser);
        Socialite::shouldReceive('driver')->with('microsoft')->andReturn($provider);
    }

    public function test_bledne_haslo_ma_polski_komunikat(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->from(route('password.confirm'))->post(route('password.confirm'), ['password' => 'zle-haslo'])
            ->assertSessionHasErrors(['password' => 'Podane hasło jest nieprawidłowe.']);
    }

    public function test_przycisk_microsoft_tylko_gdy_wlaczone(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($user)->get(route('password.confirm'))->assertOk()
            ->assertSee('Potwierdź hasłem')->assertDontSee('Potwierdź przez Microsoft 365');

        $this->enableMicrosoft();
        $this->actingAs($user)->get(route('password.confirm'))->assertOk()
            ->assertSee('Potwierdź przez Microsoft 365')->assertSee(route('auth.microsoft.confirm'), false);
    }

    public function test_potwierdzenie_kontem_microsoft_tej_samej_osoby(): void
    {
        $this->enableMicrosoft();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'microsoft_id' => 'ms-123']);
        $this->fakeMicrosoftUser('ms-123', $user->email);

        $this->actingAs($user)->get(route('auth.microsoft.confirm'))
            ->assertRedirect('https://login.microsoftonline.com/fake')
            ->assertSessionHas(MicrosoftAuthController::CONFIRM_SESSION_KEY, $user->id);

        $this->actingAs($user)->withSession([MicrosoftAuthController::CONFIRM_SESSION_KEY => $user->id, 'url.intended' => route('admin.cron.index')])
            ->get(route('auth.microsoft.callback'))
            ->assertRedirect(route('admin.cron.index'))
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_cudze_konto_microsoft_nie_potwierdza(): void
    {
        $this->enableMicrosoft();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'microsoft_id' => 'ms-123']);
        $this->fakeMicrosoftUser('ms-999', 'ktos.inny@example.com');

        $this->actingAs($user)->withSession([MicrosoftAuthController::CONFIRM_SESSION_KEY => $user->id])
            ->get(route('auth.microsoft.callback'))
            ->assertRedirect(route('password.confirm'))
            ->assertSessionMissing('auth.password_confirmed_at')
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'nie należy do zalogowanej osoby'));
    }

    public function test_konto_bez_powiazania_potwierdza_po_adresie_email(): void
    {
        $this->enableMicrosoft();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'jan@feer.org.pl']);
        $this->fakeMicrosoftUser('ms-555', 'Jan@FEER.org.pl');

        $this->actingAs($user)->withSession([MicrosoftAuthController::CONFIRM_SESSION_KEY => $user->id])
            ->get(route('auth.microsoft.callback'))
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_zalogowany_poza_trybem_potwierdzenia_trafia_do_panelu(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->get(route('auth.microsoft.callback'))->assertRedirect(route('dashboard', absolute: false));
    }
}
