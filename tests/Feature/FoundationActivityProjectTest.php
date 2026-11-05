<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Uproszczona forma działania: Fundacja coś robi (np. wyjazd na wydarzenie) i publikuje relację, bez uczestników. */
class FoundationActivityProjectTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $kind): Project
    {
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();
        $cat = Category::create(['name' => 'Wydarzenia', 'slug' => 'wydarzenia']);

        return Project::create(['category_id' => $cat->id, 'title' => 'Wyjazd na konferencję', 'slug' => 'wyjazd', 'kind' => $kind,
            'content' => '<p>Relacja z wyjazdu</p>', 'for_whom' => 'Osoby z niepełnosprawnościami i ich rodziny', 'is_published' => true,
            'starts_on' => '2026-10-20', 'main_cta' => ['label' => 'Zapisz się', 'url' => '/zapisy']])->fresh();
    }

    public function test_public_page_shows_report_without_participation_info(): void
    {
        $html = $this->get(route('projects.show', $this->project('foundation_activity')))->assertOk()->getContent();

        $this->assertStringContainsString('Relacja z wyjazdu', $html);
        $this->assertStringContainsString('>Relacja</h2>', $html);
        $this->assertStringContainsString('20 października 2026', $html);
        $this->assertStringNotContainsString('Dla kogo', $html);
        $this->assertStringNotContainsString('Bezpłatny', $html);
        $this->assertStringNotContainsString('Zapisz się', $html);
    }

    public function test_regular_project_is_unchanged(): void
    {
        $html = $this->get(route('projects.show', $this->project('project')))->assertOk()->getContent();

        $this->assertStringContainsString('Opis działania', $html);
        $this->assertStringContainsString('Bezpłatny', $html);
    }

    public function test_admin_saves_kind_and_clears_paid_flag(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $project = $this->project('project');
        $project->update(['is_paid' => true]);

        $form = $this->actingAs($admin)->get(route('admin.projekty.edit', $project))->assertOk()->getContent();
        $this->assertStringContainsString('Działanie Fundacji (bez uczestników, z relacją)', $form);
        // Pola zbędne przy relacji są oznaczone do ukrycia po wybraniu tego rodzaju.
        $this->assertStringContainsString('Brak banerów', $form);
        $this->assertGreaterThanOrEqual(10, substr_count($form, 'data-participant-only'));
        foreach (['name="starts_on"', 'name="excerpt"'] as $kept) {
            $this->assertStringContainsString($kept, $form);
        }

        $this->actingAs($admin)->put(route('admin.projekty.update', $project), [
            'title' => 'Wyjazd na konferencję', 'slug' => 'wyjazd', 'category_id' => $project->category_id,
            'kind' => 'foundation_activity', 'is_paid' => '1', 'layout_template' => 'classic',
        ])->assertSessionHasNoErrors();

        $fresh = $project->fresh();
        $this->assertSame('foundation_activity', $fresh->kind);
        $this->assertFalse((bool) $fresh->is_paid);
    }
}
