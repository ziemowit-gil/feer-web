<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Strona działania: pasek najważniejszych informacji, kontakt i powrót w menu bocznym, metka. */
class ProjectPageImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $cat = Category::create(['name' => 'Szkolenia', 'slug' => 'szkolenia']);
        $p = Project::create(['category_id' => $cat->id, 'title' => 'Akademia', 'slug' => 'akademia', 'content' => '<p>Opis</p>', 'is_published' => true,
            'for_whom' => 'Nauczyciele', 'since' => '2020', 'coordinator_name' => 'Jan Kowalski', 'coordinator_email' => 'jan@example.org',
            'show_coordinator' => true, 'sections_nav' => 'sidebar']);
        Page::create(['title' => 'Zakres', 'slug' => 'zakres-ak', 'type' => 'standard', 'is_published' => true, 'project_id' => $p->id, 'project_display' => 'tab', 'content' => '<p>Zakres</p>']);

        return $p->fresh();
    }

    public function test_key_facts_contact_card_and_back_links(): void
    {
        $p = $this->project();
        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Najważniejsze informacje"', $html);
        $this->assertStringContainsString('Bezpłatny', $html);
        $this->assertStringContainsString('Nauczyciele', $html);

        $nav = substr($html, strpos($html, 'aria-label="Sekcje działania"'));
        $nav = substr($nav, 0, strpos($nav, '</nav>'));
        $this->assertStringContainsString('Masz pytania? Chętnie odpowiemy', $nav);
        $this->assertStringContainsString('fa-user', $nav);
        $this->assertStringContainsString('Wszystkie działania', $nav);
        $this->assertStringContainsString(route('categories.show', $p->category), $nav);
        $this->assertSame(1, substr_count($html, 'Masz pytania? Chętnie odpowiemy'), 'Kontakt tylko raz (w menu bocznym).');
    }

    public function test_metka_on_project_when_enabled(): void
    {
        $p = $this->project();
        $this->get(route('projects.show', $p))->assertOk()->assertDontSee('Pokaż metkę');

        SiteSetting::current()->update(['metka_mode' => 'types', 'metka_types' => [Project::METKA_TYPE]]);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $this->get(route('projects.show', $p))->assertOk()->assertSee('Pokaż metkę')->assertSee('Data publikacji');
    }

    public function test_coordinator_photo_from_team_person_page(): void
    {
        $p = $this->project();
        Page::create(['title' => 'Jan Kowalski', 'slug' => 'jan-kowalski', 'type' => 'about_person', 'is_published' => true,
            'content_image' => 'https://example.test/jan.jpg', 'person_email' => 'JAN@example.org']);

        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();
        $nav = substr($html, strpos($html, 'aria-label="Sekcje działania"'));
        $this->assertStringContainsString('src="https://example.test/jan.jpg"', substr($nav, 0, strpos($nav, '</nav>')));
    }

    public function test_child_project_uses_parent_coordinator_when_own_empty(): void
    {
        $parent = $this->project();
        $child = Project::create(['category_id' => $parent->category_id, 'parent_id' => $parent->id, 'title' => 'Moduł 1', 'slug' => 'modul-1',
            'content' => '<p>Moduł</p>', 'is_published' => true]);

        $this->assertSame('Jan Kowalski', Project::find($child->id)->coordinator_name);

        $child->update(['coordinator_name' => 'Anna Nowak', 'coordinator_email' => 'anna@example.org']);
        $this->assertSame('Anna Nowak', Project::find($child->id)->coordinator_name, 'Własny koordynator ma pierwszeństwo.');
    }
}
