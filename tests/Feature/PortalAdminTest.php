<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Panel: menu „Portale” — lista portali, utworzenie i edycja portalu kategorii. */
class PortalAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_lista_portali_i_kategorii_bez_portalu(): void
    {
        Category::create(['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia-adm', 'portal_slug' => 'szkoleniaadm', 'portal_title' => 'Portal szkoleń']);
        $without = Category::create(['name' => 'Wolontariat i staże', 'slug' => 'wolontariat-adm']);

        $this->actingAs($this->admin())->get(route('admin.portale.index'))->assertOk()
            ->assertSee('Portal szkoleń')->assertSee('/portal/szkoleniaadm')
            ->assertSee('Utwórz portal z kategorii')->assertSee('Wolontariat i staże')
            ->assertSee(route('admin.portale.edit', $without), false)
            ->assertSee(route('admin.portale.index'), false); // pozycja „Portale” w menu panelu
    }

    public function test_utworzenie_i_edycja_portalu(): void
    {
        $category = Category::create(['name' => 'Edukacja i materiały', 'slug' => 'edukacja-adm']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.portale.edit', $category))->assertOk()
            ->assertSee('Nowy portal: Edukacja i materiały')->assertSee('Menu portalu')->assertSee('Lead');

        $this->actingAs($admin)->put(route('admin.portale.update', $category), [
            'portal_slug' => 'edukacjaadm', 'portal_title' => 'Edukacja',
            'portal_widgets' => ['news', 'contact'], 'portal_widgets_order' => 'contact,news',
            'portal_options' => ['lead' => 'Materiały i kursy dla każdego.', 'stay_enabled' => '1'],
        ])->assertRedirect(route('admin.portale.edit', $category))->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('edukacjaadm', $category->portal_slug);
        $this->assertSame(['contact', 'news'], $category->portal_widgets);
        $this->assertSame('Materiały i kursy dla każdego.', $category->portalOption('lead'));
        $this->get('/portal/edukacjaadm')->assertOk()->assertSee('Materiały i kursy dla każdego.');

        $this->actingAs($admin)->put(route('admin.portale.update', $category), ['portal_slug' => 'cms2'])
            ->assertSessionHasErrors('portal_slug');
    }

    public function test_zapis_kategorii_nie_kasuje_portalu(): void
    {
        $category = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia-adm2', 'portal_slug' => 'szkoleniaadm2', 'portal_widgets' => ['news'], 'portal_options' => ['lead' => 'Lead']]);

        $this->actingAs($this->admin())->put(route('admin.kategorie.update', $category), ['name' => 'Szkolenia i kursy', 'slug' => 'szkolenia-adm2'])
            ->assertSessionHasNoErrors();

        $category->refresh();
        $this->assertSame('Szkolenia i kursy', $category->name);
        $this->assertSame('szkoleniaadm2', $category->portal_slug);
        $this->assertSame(['news'], $category->portal_widgets);
        $this->assertSame('Lead', $category->portalOption('lead'));
        $this->actingAs($this->admin())->get(route('admin.kategorie.edit', $category))->assertOk()
            ->assertSee('Portal kategorii')->assertSee(route('admin.portale.edit', $category), false);
    }
}
