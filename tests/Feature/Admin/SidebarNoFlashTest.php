<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNoFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_boczne_ustawia_stan_przed_pierwszym_malowaniem_i_bez_animacji_na_starcie(): void
    {
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString("localStorage.getItem('admin-sidebar') === '1') document.getElementById('admin-sidebar')", $html);
        $this->assertStringContainsString('html:not(.admin-ready) .admin-sidebar', $html);
        $this->assertStringContainsString('data-nav-key=', $html);
        $this->assertStringContainsString("classList.add('admin-ready')", $html);
    }
}
