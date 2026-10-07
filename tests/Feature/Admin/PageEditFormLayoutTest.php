<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageEditFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_formularz_edycji_strony_ma_naglowek_ze_statusem_typem_i_przycisk_zapisu(): void
    {
        $page = Page::create(['title' => 'Strona testowa', 'slug' => 'strona-testowa', 'type' => 'links_hub', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))
            ->assertOk()
            ->assertSee('Edycja strony')
            ->assertSee('Opublikowana')
            ->assertSee('Strona z kafelkami — metro')
            ->assertSee('form="page-edit-form"', false)
            ->assertSee('id="page-edit-form"', false);
    }

    public function test_typ_szablon_i_styl_nawigacji_wybiera_sie_w_oknie_dialogowym(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-picker', 'type' => 'standard', 'is_published' => true]);

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()->getContent();

        foreach (['type-picker', 'template-picker', 'nav-style-picker'] as $id) {
            $this->assertStringContainsString($id, $html);
        }
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('data-page-type-select', $html); // natywny select zostaje nośnikiem wartości
        $this->assertStringContainsString('name="side_nav_style"', $html);
    }

    public function test_zakladka_plikow_ma_strefe_upuszczania(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona-pliki', 'type' => 'standard', 'is_published' => true]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('Kliknij, aby wybrać plik, albo przeciągnij go tutaj');
    }

    public function test_edytor_kafelkow_ma_podglad_paleta_i_segmenty(): void
    {
        $page = Page::create(['title' => 'Siatka', 'slug' => 'siatka-test', 'type' => 'tiles_grid', 'is_published' => true,
            'tiles' => [['label' => 'Panel kursanta', 'url' => '/panel', 'icon' => 'bi-person', 'color' => '#1e6dff', 'cols' => 2, 'strip' => false, 'is_negative' => true]]]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.podstrony.edit', $page))->assertOk()
            ->assertSee('Podgląd kafelka')
            ->assertSee('Kolory z brandbooka')
            ->assertSee('tiles[0][label]', false)
            ->assertSee('tiles[__INDEX__][label]', false)
            ->assertSee('Negatyw');
    }
}
