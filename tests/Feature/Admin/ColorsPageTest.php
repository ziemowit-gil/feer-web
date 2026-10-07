<?php

namespace Tests\Feature\Admin;

use App\Models\NavItem;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_strona_kolorow_wymaga_admina_i_sie_wyswietla(): void
    {
        $this->get(route('admin.kolory.edit'))->assertRedirect();
        $this->actingAs($this->admin())->get(route('admin.kolory.edit'))->assertOk()->assertSee('Gotowe palety')->assertSee('Podgląd na żywo');
    }

    public function test_zapis_palety_nie_przyciemnia_kolorow_firmowych(): void
    {
        $this->actingAs($this->admin())->put(route('admin.kolory.update'), [
            'brand_color' => '#1E6DFF', 'brand_color_2' => '#ea8f00', 'brand_color_3' => '#1d1d1a', 'brand_color_4' => '#cbd5e7', 'ngo_color' => '#ea8f00',
        ])->assertRedirect(route('admin.kolory.edit'));

        $s = SiteSetting::query()->first();
        $this->assertSame('#1e6dff', $s->brand_color);
        $this->assertSame('#1d1d1a', $s->brand_color_3);
        $this->assertTrue((bool) $s->brand_skip_contrast);
    }

    public function test_niepoprawny_kolor_jest_odrzucony(): void
    {
        $this->actingAs($this->admin())->put(route('admin.kolory.update'), ['brand_color' => 'niebieski'])->assertSessionHasErrors('brand_color');
    }

    public function test_narzedzia_masowe_ustawiaja_kolory_akcji_i_czyszcza_menu(): void
    {
        QuickAction::create(['label' => 'A', 'url' => '/a', 'icon' => 'bi-star', 'order' => 1]);
        QuickAction::create(['label' => 'B', 'url' => '/b', 'icon' => 'bi-star', 'order' => 2]);
        NavItem::create(['label' => 'M', 'url' => '/m', 'type' => 'link', 'location' => 'main', 'is_active' => true, 'order' => 1, 'accent_color' => '#123456']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.kolory.brandbook-akcje'))->assertRedirect();
        $this->assertSame(['#1e6dff', '#1d1d1a'], QuickAction::orderBy('order')->pluck('color')->all());

        $this->actingAs($admin)->post(route('admin.kolory.wyczysc-menu'))->assertRedirect();
        $this->assertNull(NavItem::first()->accent_color);
    }

    public function test_strona_kolorow_pokazuje_podglad_pasków_gdy_modul_wlaczony(): void
    {
        $this->actingAs($this->admin())->get(route('admin.kolory.edit'))->assertOk()->assertSee('Zarządzaj paskami')->assertSee('Styl „Ciemny”');
    }
}
