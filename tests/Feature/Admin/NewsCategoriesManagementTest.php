<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsCategoriesManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function news(NewsCategory $cat, string $title = 'News'): News
    {
        return News::create(['title' => $title, 'slug' => str($title)->slug().'-'.uniqid(), 'content' => '<p>x</p>', 'is_published' => true, 'published_at' => now(), 'news_category_id' => $cat->id]);
    }

    public function test_lista_pokazuje_szybkie_dodawanie_liczniki_i_linki(): void
    {
        $cat = NewsCategory::create(['name' => 'Archiwum', 'slug' => 'archiwum', 'order' => 1]);
        $this->news($cat);

        $this->actingAs($this->admin())->get(route('admin.kategorie-newsow.index'))->assertOk()
            ->assertSee('Dodaj kategorię')->assertSee('Archiwum')->assertSee('/aktualnosci?kategoria=archiwum')->assertSee('1 news');
    }

    public function test_szybkie_dodanie_tworzy_kategorie_z_unikalnym_slugiem(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.kategorie-newsow.store'), ['name' => 'Wydarzenia', 'color' => '#1e6dff'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.kategorie-newsow.store'), ['name' => 'Wydarzenia'])->assertRedirect();

        $this->assertSame(['wydarzenia', 'wydarzenia-2'], NewsCategory::orderBy('id')->pluck('slug')->all());
    }

    public function test_przesuwanie_zmienia_kolejnosc(): void
    {
        $a = NewsCategory::create(['name' => 'A', 'slug' => 'a', 'order' => 1]);
        $b = NewsCategory::create(['name' => 'B', 'slug' => 'b', 'order' => 2]);

        $this->actingAs($this->admin())->post(route('admin.kategorie-newsow.move', $b), ['direction' => 'up'])->assertRedirect();

        $this->assertSame(['B', 'A'], NewsCategory::orderBy('order')->pluck('name')->all());
    }

    public function test_usuniecie_kategorii_z_newsami_wymaga_wskazania_celu_i_nie_gubi_newsow(): void
    {
        $a = NewsCategory::create(['name' => 'A', 'slug' => 'a', 'order' => 1]);
        $b = NewsCategory::create(['name' => 'B', 'slug' => 'b', 'order' => 2]);
        $n = $this->news($a);
        $admin = $this->actingAs($this->admin());

        $admin->delete(route('admin.kategorie-newsow.destroy', $a))->assertSessionHas('error');
        $this->assertNotNull(NewsCategory::find($a->id));

        $admin->delete(route('admin.kategorie-newsow.destroy', $a), ['move_to' => $b->id])->assertSessionHas('status');
        $this->assertNull(NewsCategory::find($a->id));
        $this->assertSame($b->id, $n->fresh()->news_category_id);
    }

    public function test_usuniecie_z_opcja_bez_kategorii_zeruje_kategorie_newsow(): void
    {
        $a = NewsCategory::create(['name' => 'A', 'slug' => 'a', 'order' => 1]);
        $n = $this->news($a);

        $this->actingAs($this->admin())->delete(route('admin.kategorie-newsow.destroy', $a), ['move_to' => 'none'])->assertSessionHas('status');

        $this->assertNull($n->fresh()->news_category_id);
    }

    public function test_z_listy_i_formularza_aktualnosci_jest_widoczny_link_do_kategorii(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.newsy.index'))->assertOk()->assertSee('Kategorie (')->assertSee(route('admin.kategorie-newsow.index'), false);
        $this->actingAs($admin)->get(route('admin.newsy.create'))->assertOk()->assertSee('Zarządzaj kategoriami aktualności')->assertSee('Nie ma jeszcze żadnych kategorii');
    }
}
