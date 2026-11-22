<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edytor blokowy domyślny także przy dodawaniu strony: formularz bez pola treści, a po zapisie strona z treścią
 * otwiera się w edytorze blokowym. ?klasyczny=1 i wybór „Klasyczny” w profilu zostawiają dawny formularz.
 */
class PageCreateBlockEditorDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_nowa_strona_otwiera_sie_w_edytorze_blokowym(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => null]);

        $this->actingAs($admin)->get(route('admin.podstrony.create'))->assertOk()
            ->assertSee('data-block-first', false)
            ->assertSee('name="block_first"', false)
            ->assertSee('Treść dodasz w edytorze blokowym.');

        $response = $this->post(route('admin.podstrony.store'), ['title' => 'Nowa strona', 'type' => 'standard', 'parent_id' => '', 'block_first' => '1']);
        $page = Page::query()->where('title', 'Nowa strona')->firstOrFail();
        $response->assertRedirect(route('admin.podstrony.bloki.edit', $page));
    }

    public function test_formularz_klasyczny_na_zyczenie_i_z_profilu(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => null]);

        $this->actingAs($admin)->get(route('admin.podstrony.create', ['klasyczny' => 1]))->assertOk()
            ->assertDontSee('data-block-first', false);

        $classic = User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => User::EDITOR_CLASSIC]);
        $this->actingAs($classic)->get(route('admin.podstrony.create'))->assertOk()->assertDontSee('data-block-first', false);
        $this->post(route('admin.podstrony.store'), ['title' => 'Z formularza', 'type' => 'standard', 'parent_id' => ''])
            ->assertRedirect(route('admin.podstrony.index'));
    }

    public function test_typy_bez_swobodnej_tresci_wracaja_do_listy(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'default_editor' => null]);

        $this->actingAs($admin)->post(route('admin.podstrony.store'), ['title' => 'Kontakt', 'type' => 'contact', 'parent_id' => '', 'block_first' => '1'])
            ->assertRedirect(route('admin.podstrony.index'));
    }
}
