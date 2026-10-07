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
}
