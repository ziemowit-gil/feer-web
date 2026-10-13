<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Edytor blokowy jako moduł: po wyłączeniu w Ustawienia → Moduły znikają przyciski, a trasy zwracają 404. */
class BlockEditorModuleToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_editor_can_be_disabled(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertSee('Otwórz w edytorze blokowym');

        SiteSetting::current()->update(['disabled_modules' => ['block_editor']]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->actingAs($admin)->get(route('admin.podstrony.edit', $page))->assertOk()->assertDontSee('Otwórz w edytorze blokowym');
        $this->actingAs($admin)->get(route('admin.podstrony.bloki.edit', $page))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.edytor-blokowy.edit', ['page', $page->id]))->assertNotFound();
    }
}
