<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        \Closure::bind(function () {
            static::$cached = null;
        }, null, SiteSetting::class)();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_admin_can_edit_project_fields_on_the_page(): void
    {
        $project = Project::create(['title' => 'Stary tytuł', 'slug' => 'projekt', 'is_published' => true, 'content' => '<p>a</p>']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inline-edit.update'), ['model' => 'project', 'id' => $project->id, 'field' => 'content', 'value' => '<h2>Nowy</h2><p>opis</p>'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inline-edit.update'), ['model' => 'project', 'id' => $project->id, 'field' => 'title', 'value' => 'Nowy tytuł'])
            ->assertOk();

        $project->refresh();
        $this->assertSame('<h2>Nowy</h2><p>opis</p>', $project->content);
        $this->assertSame('Nowy tytuł', $project->title);
    }

    public function test_project_inline_edit_rejects_unknown_field_and_wrong_role(): void
    {
        $project = Project::create(['title' => 'Projekt', 'slug' => 'projekt', 'is_published' => true]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inline-edit.update'), ['model' => 'project', 'id' => $project->id, 'field' => 'slug', 'value' => 'x'])
            ->assertStatus(422);

        $bipEditor = User::factory()->create(['role' => User::ROLE_BIP_EDITOR]);
        $this->actingAs($bipEditor)
            ->putJson(route('admin.inline-edit.update'), ['model' => 'project', 'id' => $project->id, 'field' => 'title', 'value' => 'x'])
            ->assertForbidden();
    }

    public function test_project_page_exposes_inline_fields_only_to_editors(): void
    {
        $project = Project::create(['title' => 'Projekt', 'slug' => 'projekt', 'is_published' => true, 'content' => '<p>opis</p>']);

        $this->get(route('projects.show', $project))->assertOk()->assertDontSee('data-inline-field', false);

        $this->actingAs($this->admin())->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-inline-field="title"', false)
            ->assertSee('data-inline-field="content" data-inline-kind="rich"', false)
            ->assertSee('Edytuj tę stronę');
    }

    public function test_news_quick_update_saves_content_when_sent(): void
    {
        $news = News::create(['title' => 'News', 'slug' => 'news', 'is_published' => true, 'published_at' => now(), 'content' => '<p>stara</p>']);

        $this->actingAs($this->admin())
            ->patchJson(route('admin.newsy.szybka-edycja', $news), ['title' => 'News 2', 'excerpt' => 'lead', 'is_published' => true, 'content' => '<p>nowa</p>'])
            ->assertOk();

        $this->assertSame('<p>nowa</p>', $news->fresh()->content);

        // Bez pola content treść zostaje nietknięta (starszy pasek edycji).
        $this->actingAs($this->admin())
            ->patchJson(route('admin.newsy.szybka-edycja', $news), ['title' => 'News 3', 'excerpt' => 'lead', 'is_published' => true])
            ->assertOk();

        $this->assertSame('<p>nowa</p>', $news->fresh()->content);
        $this->assertSame('News 3', $news->fresh()->title);
    }

    public function test_page_inline_edit_still_works(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'is_published' => true]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inline-edit.update'), ['model' => 'page', 'id' => $page->id, 'field' => 'title', 'value' => 'Strona 2'])
            ->assertOk();

        $this->assertSame('Strona 2', $page->fresh()->title);
    }
}
