<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu boczne: grupa „Dokumenty i BIP” tylko przy włączonym module BIP; sprawozdania wtedy w „Działalności”. */
class AdminMenuBipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function menuFor(bool $bip): string
    {
        $manager = app(\App\Modules\ModuleManager::class);
        if ($manager->get('bip') !== null) {
            \Closure::bind(function () use ($bip) { $this->statuses->put('bip', $bip ? 'active' : 'inactive'); }, $manager, \App\Modules\ModuleManager::class)();
        }
        $site = SiteSetting::current();
        $disabled = collect($site->disabled_modules ?? [])->reject(fn ($m) => $m === 'bip')->values()->all();
        $site->update(['disabled_modules' => $bip ? $disabled : [...$disabled, 'bip']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        return $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))->get(route('admin.dashboard'))->assertOk()->getContent();
    }

    public function test_bez_modulu_bip_nie_ma_grupy_dokumenty_i_bip(): void
    {
        $off = $this->menuFor(false);
        $this->assertStringNotContainsString('id="tm-panel-docs"', $off);
        $this->assertStringNotContainsString('Menu BIP', $off);

        $on = $this->menuFor(true);
        $this->assertStringContainsString('id="tm-panel-docs"', $on);
        $this->assertStringContainsString('Dokumenty i BIP', $on);
    }
}
