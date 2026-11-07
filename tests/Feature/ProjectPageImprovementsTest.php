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
        $after = substr($nav, strpos($nav, '</nav>'), 3000);
        $nav = substr($nav, 0, strpos($nav, '</nav>'));
        // Kontakt jako osobny blok pod menu (poza <nav>).
        $this->assertStringContainsString('Masz pytania? Chętnie odpowiemy', $after);
        $this->assertStringContainsString('Jan Kowalski', $after);
        $this->assertStringNotContainsString('fa-user', $after, 'Bez zdjęcia nie ma zastępczej ikony.');
        $this->assertStringContainsString(route('categories.show', $p->category), $html);
        $this->assertStringContainsString('Wszystkie działania', $html);
        $this->assertSame(1, substr_count($html, 'Masz pytania? Chętnie odpowiemy'), 'Kontakt tylko raz (w menu bocznym).');
    }

    public function test_odnosnik_do_strony_o_odplatach_nazywa_sie_jak_i_dlaczego_pobieramy_odplaty(): void
    {
        // Strona z zasadami odpłatności istnieje po migracjach; w razie braku tworzymy ją na potrzeby testu.
        $rules = Page::firstOrCreate(['slug' => 'odplatna-dzialalnosc-pozytku-publicznego'], ['title' => 'Odpłatna działalność pożytku publicznego', 'type' => 'standard', 'content' => '<p>Zasady</p>']);
        $rules->forceFill(['is_published' => true, 'is_disabled' => false])->save();
        $p = $this->project();
        $p->update(['kind' => 'paid_offer']);
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $html = $this->get(route('projects.show', $p->fresh()))->assertOk()->getContent();

        $this->assertStringContainsString('Jak i dlaczego pobieramy odpłaty', $html);
        $this->assertStringContainsString('href="'.route('page.show', $rules).'"', $html);
        $this->assertStringNotContainsString('Zasady odpłatnej działalności', $html);
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
        $this->assertStringContainsString('src="https://example.test/jan.jpg"', $html);
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

    public function test_contact_redirect_keeps_query_string(): void
    {
        \App\Models\Page::create(['title' => 'Kontakt', 'slug' => 'kontakt-2', 'type' => 'standard', 'is_published' => true]);
        \App\Models\Page::where('slug', 'kontakt-2')->update(['type' => 'contact']);
        $this->get('/kontakt?do=ewelina.fraczek%40feer.org.pl')->assertRedirect();
        $this->assertStringContainsString('do=ewelina.fraczek', (string) $this->get('/kontakt?do=ewelina.fraczek%40feer.org.pl')->headers->get('Location'));
    }

    public function test_info_sections_do_not_use_the_flat_row_frame_class(): void
    {
        $p = $this->project();
        SiteSetting::current()->update(['projects_terms_enabled' => true]);
        $p->update(['terms' => [['label' => 'Wiek', 'text' => 'od 18 lat']]]);

        $html = $this->get(route('projects.show', $p))->assertOk()->getContent();

        $this->assertStringContainsString('<section class="proj-sec" aria-labelledby="proj-terms-h">', $html);
        $this->assertStringNotContainsString('<section class="proj-frame"', $html, 'Klasa .proj-frame to wiersz flex z <x-proj-frame> — sekcje nie mogą jej dziedziczyć.');
    }
}
