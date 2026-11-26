<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ustawienia → Treści: „Wymuś boczne menu FEER na wszystkich podstronach z podmenu” (poza działaniami). */
class ForcePanelNavTest extends TestCase
{
    use RefreshDatabase;

    private const PANEL = '<nav aria-label="Podstrony w tym dziale" class="rounded-md bg-gray-50 p-5">';

    protected function setUp(): void
    {
        parent::setUp();
        $this->reset();
    }

    private function reset(): void
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
    }

    private function section(array $parent = []): Page
    {
        $root = Page::create(['title' => 'Dział', 'slug' => 'dzial', 'type' => 'standard', 'is_published' => true, 'show_side_nav' => false, 'side_nav_style' => 'tabs'] + $parent);
        Page::create(['title' => 'Podstrona A', 'slug' => 'podstrona-a', 'type' => 'standard', 'is_published' => true, 'parent_id' => $root->id, 'show_side_nav' => false]);

        return $root;
    }

    public function test_bez_opcji_strona_zachowuje_wlasne_ustawienia(): void
    {
        $this->section();

        $this->get('/podstrona-a')->assertOk()->assertDontSee(self::PANEL, false);
    }

    public function test_opcja_wymusza_panel_boczny_na_podstronach_z_podmenu(): void
    {
        $this->section();
        Page::create(['title' => 'Samotna', 'slug' => 'samotna', 'type' => 'standard', 'is_published' => true]);
        SiteSetting::current()->update(['force_panel_nav' => true]);
        $this->reset();

        $this->get('/podstrona-a')->assertOk()->assertSee(self::PANEL, false)->assertSee('Podstrona A');
        $this->get('/dzial')->assertOk()->assertSee(self::PANEL, false);
        // Strona bez podmenu — nic do pokazania.
        $this->get('/samotna')->assertOk()->assertDontSee('Podstrony w tym dziale');
    }

    public function test_strony_dzialan_nie_sa_zmieniane(): void
    {
        $cat = \App\Models\Category::create(['name' => 'Kat', 'slug' => 'kat']);
        $project = Project::create(['title' => 'Działanie', 'slug' => 'dzialanie', 'category_id' => $cat->id, 'is_published' => true]);
        $root = $this->section(['project_id' => $project->id]);
        SiteSetting::current()->update(['force_panel_nav' => true]);
        $this->reset();

        $this->assertFalse($root->fresh()->forcesPanelNav());
        $this->assertFalse(Page::where('slug', 'podstrona-a')->first()->forcesPanelNav());
        $this->assertSame('tabs', Page::where('slug', 'podstrona-a')->first()->sideNavStyle());
    }

    public function test_przelacznik_w_ustawieniach_zapisuje_opcje(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.ustawienia.edit', ['tab' => 'content']))->assertOk()
            ->assertSee('Wymuś boczne menu FEER na wszystkich podstronach z podmenu');

        $this->actingAs($admin)->put(route('admin.ustawienia.update'), [
            '_section_fields' => ['force_panel_nav'], 'force_panel_nav' => '1',
        ])->assertSessionHasNoErrors();
        $this->reset();

        $this->assertTrue(SiteSetting::current()->force_panel_nav);
    }
}
