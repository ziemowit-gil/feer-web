<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Moduł Blog aktywny w bazie, ale bez załadowanych tras (np. zaraz po włączeniu w panelu, przed kolejnym startem
 * aplikacji): panel nie może paść na „Route [admin.komentarze-bloga.index] not defined”.
 */
class BlogModuleRoutesGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        parent::tearDown();
    }

    public function test_panel_dziala_po_wlaczeniu_bloga_przed_zaladowaniem_tras(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        SiteSetting::current()->forceFill(['site_template' => 'kreatywni'])->save();
        $modules = app(ModuleManager::class);
        $modules->loadStatuses();
        DB::table('plugins')->where('identifier', 'blog')->update(['status' => 'inactive']);
        $modules->loadStatuses();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        if (Route::has('admin.komentarze-bloga.index')) {
            $this->markTestSkipped('Trasy bloga są już załadowane w tym środowisku testów.');
        }

        $this->post(route('admin.moduly.activate', 'blog'))->assertRedirect();
        $this->assertTrue($modules->isActive('blog'));

        $this->get('/admin/moduly')->assertOk();
        $this->get('/admin/kreatywni-oferty')->assertOk();
    }
}
