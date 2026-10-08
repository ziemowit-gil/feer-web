<?php

use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * Przenosi dotychczasowe „własne sekcje" projektów (JSON w projects.custom_sections)
 * do drzewa podstron projektu: każda sekcja staje się stroną powiązaną z projektem
 * (zakładka, gdy włączono „sekcje jako zakładki", w przeciwnym razie sekcja w treści).
 * Idempotentna — po przeniesieniu pole custom_sections jest czyszczone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $taken = fn (string $slug) => in_array($slug, Page::RESERVED_SLUGS, true)
            || Page::withTrashed()->withoutGlobalScopes()->where('slug', $slug)->exists();

        Project::withoutGlobalScopes()->whereNotNull('custom_sections')->get()->each(function (Project $project) use ($taken) {
            $sections = collect($project->custom_sections ?? [])
                ->filter(fn ($s) => filled($s['title'] ?? null) || filled(trim(strip_tags((string) ($s['content'] ?? '')))))
                ->values();

            foreach ($sections as $i => $section) {
                $title = trim((string) ($section['title'] ?? '')) ?: 'Sekcja '.($i + 1);
                $base = Str::slug($project->slug.'-'.$title) ?: 'strona';
                $slug = $base;
                for ($n = 2; $taken($slug); $n++) {
                    $slug = "{$base}-{$n}";
                }

                $page = new Page([
                    'site_id' => $project->site_id,
                    'project_id' => $project->id,
                    'project_display' => $project->sections_as_tabs ? 'tab' : 'inline',
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $section['content'] ?? '',
                    'is_published' => (bool) $project->is_published,
                    'show_in_menu' => false,
                    'order' => $i,
                ]);
                $page->type = 'standard';
                $page->save();
            }

            $project->forceFill(['custom_sections' => null, 'sections_as_tabs' => false])->save();
        });
    }

    public function down(): void
    {
        // Przeniesienia nie cofamy — podstrony zostają w drzewie projektu.
    }
};
