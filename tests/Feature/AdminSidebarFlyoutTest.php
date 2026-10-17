<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu boczne: na szerokim ekranie grupy wysuwane w prawo (panel z pozycjami i podpozycjami), na telefonie akordeon. */
class AdminSidebarFlyoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_grupy_maja_panel_wysuwany_w_prawo_z_podpozycjami(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('flyout() { return this.collapsed || window.matchMedia(\'(min-width: 1024px)\').matches; }', $html);
        $this->assertStringContainsString('x-show="fly && $store.adminNav.flyout()"', $html);
        $this->assertStringContainsString("'top:' + flyTop + 'px; left:' + flyLeft + 'px'", $html);
        $this->assertStringContainsString('.admin-sidebar .tm-panel { display: none !important; }', $html);
        $this->assertStringContainsString('@keydown.escape.stop="if (fly)', $html);
        // Podpozycja (Strefy bannerów pod Bannery) także w wysuwanym panelu.
        $this->assertStringContainsString('class="nav-flyout', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.route('admin.strefy-bannerow.index').'"'));
    }
}
