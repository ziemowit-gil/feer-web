<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Szablony strony działania do wyboru w panelu: klasyczny (obecny), panel po lewej i trzy kolumny. */
class ProjectLayoutTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $template): Project
    {
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
        SiteSetting::current()->update(['projects_extras_enabled' => true]);
        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();

        $category = Category::firstOrCreate(['slug' => 'testowa'], ['name' => 'Testowa']);
        $project = Project::create([
            'title' => 'Działanie testowe', 'slug' => 'dzialanie-testowe', 'category_id' => $category->id, 'content' => '<p>Treść.</p>',
            'is_published' => true, 'layout_template' => $template, 'main_cta' => ['label' => 'Zapisz się', 'url' => '/kontakt'],
        ]);
        Page::withoutGlobalScopes()->create(['title' => 'Podstrona', 'slug' => 'podstrona-testowa', 'type' => 'standard', 'is_published' => true,
            'project_id' => $project->id, 'project_display' => 'tab', 'content' => '<p>Podstrona.</p>']);

        return $project;
    }

    public function test_szablony_z_menu_po_boku_wymuszaja_menu_boczne(): void
    {
        $left = new Project(['layout_template' => 'left']);
        $three = new Project(['layout_template' => 'three']);

        $this->assertSame('sidebar', $left->sectionsNavMode());
        $this->assertSame('sidebar', $three->sectionsNavMode());
        $this->assertSame(['classic', 'left', 'three'], array_keys(Project::LAYOUT_TEMPLATES_INFO));
    }

    public function test_strona_ma_klase_ukladu_zgodna_z_wybranym_szablonem(): void
    {
        $classic = $this->get(route('projects.show', $this->project('classic')))->assertOk()->getContent();
        $this->assertStringContainsString('proj-cols', $classic);
        $this->assertStringNotContainsString('proj-cols--left"', $classic);

        Project::query()->update(['layout_template' => 'left']);
        $left = $this->get(route('projects.show', Project::first()))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#class="[^"]*proj-cols--left[^"]*"#', $left);

        Project::query()->update(['layout_template' => 'three']);
        $three = $this->get(route('projects.show', Project::first()))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#class="[^"]*proj-cols--three[^"]*"#', $three);
    }

    public function test_nieznany_szablon_wraca_do_klasycznego(): void
    {
        $html = $this->get(route('projects.show', $this->project('nieznany')))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('#class="[^"]*proj-cols--(left|three)[^"]*"#', $html);
    }

    public function test_panel_pokazuje_trzy_szablony_i_zapisuje_wybor(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $project = $this->project('classic');

        $form = $this->actingAs($admin)->get(route('admin.projekty.edit', $project))->assertOk()->getContent();
        $this->assertStringContainsString('Szablon strony działania', $form);
        foreach (['Klasyczny (obecny)', 'Panel po lewej', 'Trzy kolumny'] as $name) {
            $this->assertStringContainsString($name, $form);
        }

        $payload = [
            'title' => $project->title, 'slug' => $project->slug, 'category_id' => $project->category_id, 'content' => '<p>Treść.</p>',
            'is_published' => 1, 'layout_template' => 'three',
        ];
        $this->actingAs($admin)->put(route('admin.projekty.update', $project), $payload)->assertSessionHasNoErrors();
        $this->assertSame('three', $project->fresh()->layout_template);

        $this->actingAs($admin)->put(route('admin.projekty.update', $project), ['layout_template' => 'cokolwiek'] + $payload);
        $this->assertSame('classic', $project->fresh()->layout_template, 'nieznana wartość wraca do klasycznego');
    }
}
