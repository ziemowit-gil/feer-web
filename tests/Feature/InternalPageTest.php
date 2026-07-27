<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InternalPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    public function test_password_protected_page_shows_gate_then_unlocks(): void
    {
        $page = Page::create([
            'title' => 'Materiały wewnętrzne', 'slug' => 'wewnetrzne', 'type' => 'internal',
            'is_published' => true, 'access_mode' => 'password',
            'access_password' => Hash::make('sezam'), 'content' => 'Tajna treść zespołu',
        ]);

        // Bez hasła — bramka, treść ukryta.
        $this->get('/wewnetrzne')
            ->assertStatus(403)
            ->assertSee('Podaj hasło')
            ->assertDontSee('Tajna treść zespołu');

        // Złe hasło — błąd.
        $this->post(route('page.unlock', $page), ['access_password' => 'zle'])
            ->assertSessionHasErrors('access_password');

        // Dobre hasło — odblokowanie i dostęp do treści.
        $this->post(route('page.unlock', $page), ['access_password' => 'sezam'])
            ->assertRedirect(route('page.show', $page));

        $this->get('/wewnetrzne')->assertOk()->assertSee('Tajna treść zespołu');
    }

    public function test_microsoft_mode_requires_login(): void
    {
        Page::create([
            'title' => 'Dla zalogowanych', 'slug' => 'dla-zalogowanych', 'type' => 'internal',
            'is_published' => true, 'access_mode' => 'microsoft', 'content' => 'Sekcja zalogowanych',
        ]);

        // Gość → przekierowanie do logowania.
        $this->get('/dla-zalogowanych')->assertRedirect(route('login'));

        // Zalogowany → dostęp.
        $this->actingAs(User::factory()->create())
            ->get('/dla-zalogowanych')->assertOk()->assertSee('Sekcja zalogowanych');
    }

    public function test_internal_pages_are_excluded_from_search(): void
    {
        Page::create(['title' => 'Sekret wewnętrzny', 'slug' => 'sekret', 'type' => 'internal', 'is_published' => true, 'access_mode' => 'password', 'access_password' => Hash::make('x')]);

        $this->get(route('search', ['q' => 'Sekret']))->assertOk()->assertDontSee('Sekret wewnętrzny');
    }
}
