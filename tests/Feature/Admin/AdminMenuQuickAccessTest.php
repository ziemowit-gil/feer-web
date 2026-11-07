<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu boczne: ulubione i ostatnio otwarte, skrót „/” do wyszukiwarki, blok „Stan witryny” w Ustawieniach. */
class AdminMenuQuickAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_ma_ulubione_ostatnie_i_skrot_do_wyszukiwarki(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.newsy.index'))->assertOk()
            ->assertSee('class="nav-quick', false)->assertSee('Ostatnio otwarte')->assertSee('Ulubione')
            ->assertSee('navQuick(', false)->assertSee('admin-nav-quick:'.$admin->id.':', false)
            ->assertSee('naciśnij /', false)->assertSee('Dodaj tę stronę do ulubionych');
    }

    public function test_serwis_jest_w_osobnym_bloku_stan_witryny(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $settings = collect(\App\Support\AdminMenu::for($admin))->firstWhere('key', 'settings');
        $block = collect($settings['blocks'])->firstWhere('heading', 'Stan witryny');

        $this->assertNotNull($block);
        $this->assertSame(['Serwis'], array_column($block['items'], 'label'));
    }
}
