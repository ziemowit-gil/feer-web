<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ListingSection;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona „Działania” (szablon FEER): dowolne bloki z edytora blokowego pod menu kategorii. */
class ProjectsSidebarBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
        SiteSetting::current()->update(['site_template' => 'feer']);
        $this->reset();
        $category = Category::create(['name' => 'Szkolenia i warsztaty', 'slug' => 'szkolenia']);
        Project::create(['category_id' => $category->id, 'title' => 'Szkolenia z obsługi komputera', 'slug' => 'szkolenia-komputer', 'is_published' => true]);
    }

    private function reset(): void
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_administrator_dodaje_bloki_pod_menu_kategorii(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('projects.index'))->assertOk()
            ->assertSee('Dodaj bloki pod menu')
            ->assertSee(route('admin.edytor-blokowy.listing', 'projects_sidebar'), false);

        $response = $this->actingAs($admin)->get(route('admin.edytor-blokowy.listing', 'projects_sidebar'));
        $section = ListingSection::sole();
        $response->assertRedirect(route('admin.edytor-blokowy.edit', ['listing', $section->id]));
        $this->actingAs($admin)->get(route('admin.edytor-blokowy.edit', ['listing', $section->id]))->assertOk()->assertSee('block-editor', false);

        $this->actingAs($admin)->putJson(route('admin.edytor-blokowy.update', ['listing', $section->id]), [
            'title' => 'Działania — pod menu kategorii',
            'blocks' => [
                ['type' => 'heading', 'attrs' => ['text' => 'Nie wiesz, od czego zacząć?', 'level' => 2]],
                ['type' => 'buttons', 'attrs' => ['items' => [['label' => 'Napisz do nas', 'url' => '/kontakt']]]],
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        // Ponowne wejście nie tworzy drugiego rekordu.
        $this->actingAs($admin)->get(route('admin.edytor-blokowy.listing', 'projects_sidebar'))->assertRedirect();
        $this->assertSame(1, ListingSection::count());

        $html = $this->get(route('projects.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Nie wiesz, od czego zacząć?', $html);
        $this->assertStringContainsString('Napisz do nas', $html);
        $this->assertGreaterThan(strpos($html, 'aria-label="Kategorie działań"'), strpos($html, 'Nie wiesz, od czego zacząć?'), 'bloki są pod menu kategorii');
        $this->assertLessThan(strpos($html, 'Szkolenia z obsługi komputera'), strpos($html, 'Nie wiesz, od czego zacząć?'), 'bloki są w lewej kolumnie, przed listą');
        $this->assertStringNotContainsString('Dodaj bloki pod menu', $html, 'gość nie widzi linku do edycji');
    }

    public function test_puste_lub_ukryte_miejsce_nic_nie_pokazuje(): void
    {
        ListingSection::place('projects_sidebar')->update(['content' => '<p> </p>']);
        $this->get(route('projects.index'))->assertOk()->assertDontSee('data-listing-blocks', false);

        ListingSection::place('projects_sidebar')->update(['content' => '<p>Treść schowana XYZ</p>', 'is_published' => false]);
        $this->get(route('projects.index'))->assertOk()->assertDontSee('Treść schowana XYZ');
    }

    public function test_bez_dostepu_do_modulu_dzialan_brak_edycji(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_NEWSLETTER_EDITOR]);

        $this->actingAs($editor)->get(route('admin.edytor-blokowy.listing', 'projects_sidebar'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.edytor-blokowy.listing', 'nieznane'))->assertNotFound();
    }
}
