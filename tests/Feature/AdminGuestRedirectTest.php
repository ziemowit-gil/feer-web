<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Przy APP_DEBUG=false własny render błędów panelu (bootstrap/app.php) nie może zamieniać „niezalogowany”
 * na stronę błędu 500 — niezalogowany na /admin ma trafić do logowania (wcześniej 500 na kopii dev).
 */
class AdminGuestRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_niezalogowany_na_admin_trafia_do_logowania_bez_trybu_debug(): void
    {
        config(['app.debug' => false]);

        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_blad_http_w_panelu_nadal_ma_strone_bledu_panelu(): void
    {
        config(['app.debug' => false]);
        $admin = \App\Models\User::factory()->create(['role' => \App\Models\User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/tej-strony-nie-ma-'.uniqid())->assertNotFound();
    }
}
