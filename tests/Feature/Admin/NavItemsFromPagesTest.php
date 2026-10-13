<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu nawigacji: dodawanie kilku stron naraz z drzewa stron i podgląd menu na stronie. */
class NavItemsFromPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_selected_pages_and_skips_ones_already_in_menu(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $a = Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'type' => 'standard', 'is_published' => true]);
        $b = Page::create(['title' => 'Zespół', 'slug' => 'zespol', 'type' => 'standard', 'is_published' => true, 'parent_id' => $a->id]);
        NavItem::create(['label' => 'O nas', 'url' => parse_url($a->publicUrl(), PHP_URL_PATH), 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1]);

        $this->actingAs($admin)->get(route('admin.pozycje-menu.index'))->assertOk()
            ->assertSee('Dodaj strony do menu')->assertSee('już w menu')->assertSee('Podgląd menu na stronie');

        $this->actingAs($admin)->post(route('admin.pozycje-menu.ze-stron'), ['location' => 'main', 'page_ids' => [$a->id, $b->id]])
            ->assertRedirect()->assertSessionHas('status', 'Dodano do menu stron: 1.');

        $this->assertSame(2, NavItem::where('location', 'main')->count());
        $this->assertSame('Zespół', NavItem::where('location', 'main')->orderByDesc('id')->first()->label);
    }
}
