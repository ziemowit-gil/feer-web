<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edytor blokowy: zapis z edytora nie może wyzerować bloków tylko dlatego, że wyrenderowany HTML
 * różni się od zapisanego (np. po zmianie renderera) — bloki są wtedy nadal źródłem treści.
 * Zerowanie ma dotyczyć wyłącznie zmiany `content` poza edytorem.
 */
class BlockEditorStaleContentTest extends TestCase
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

    /** Bloki tak, jak zapisał je edytor (po normalizacji) — zapis z tymi samymi blokami nie zmienia kolumny `blocks`. */
    private function storedBlocks(): array
    {
        return BlockRenderer::normalize([['type' => 'paragraph', 'attrs' => ['text' => 'Z bloków']]]);
    }

    private function payload(string $title): array
    {
        return ['title' => $title, 'meta_description' => '', 'blocks' => [['id' => 'b1', 'type' => 'paragraph', 'attrs' => ['text' => 'Z bloków']]]];
    }

    public function test_page_save_keeps_blocks_when_stored_html_is_stale(): void
    {
        // Zapisany HTML różni się od tego, co wyrenderuje bieżący renderer (np. po jego zmianie), bloki są te same.
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Stary HTML</p>', 'is_published' => true]);
        $page->forceFill(['blocks' => $this->storedBlocks()])->saveQuietly();

        $this->actingAs($this->admin())
            ->putJson(route('admin.podstrony.bloki.update', $page), $this->payload('Nowy tytuł'))
            ->assertOk();

        $page->refresh();
        $this->assertSame('Nowy tytuł', $page->title);
        $this->assertSame('<p>Z bloków</p>', $page->content);
        $this->assertIsArray($page->blocks);
        $this->assertSame('Z bloków', $page->blocks[0]['attrs']['text']);
    }

    public function test_news_save_keeps_blocks_when_stored_html_is_stale(): void
    {
        $news = News::create(['title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>Stary HTML</p>', 'is_published' => true]);
        $news->forceFill(['blocks' => $this->storedBlocks()])->saveQuietly();

        $this->actingAs($this->admin())
            ->putJson(route('admin.edytor-blokowy.update', ['news', $news->id]), $this->payload('Nowy tytuł'))
            ->assertOk();

        $news->refresh();
        $this->assertSame('<p>Z bloków</p>', $news->content);
        $this->assertIsArray($news->blocks);
    }

    public function test_project_save_keeps_blocks_when_stored_html_is_stale(): void
    {
        $category = Category::firstOrCreate(['slug' => 'kategoria'], ['name' => 'Kategoria']);
        $project = Project::create(['title' => 'Działanie', 'slug' => 'dzialanie', 'category_id' => $category->id, 'content' => '<p>Stary HTML</p>', 'is_published' => true]);
        $project->forceFill(['blocks' => $this->storedBlocks()])->saveQuietly();

        $this->actingAs($this->admin())
            ->putJson(route('admin.edytor-blokowy.update', ['project', $project->id]), $this->payload('Nowy tytuł'))
            ->assertOk();

        $project->refresh();
        $this->assertSame('<p>Z bloków</p>', $project->content);
        $this->assertIsArray($project->blocks);
    }

    public function test_content_changed_outside_the_editor_still_drops_blocks(): void
    {
        $page = Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Z bloków</p>', 'is_published' => true]);
        $page->forceFill(['blocks' => $this->storedBlocks()])->saveQuietly();

        // Ten sam rekord zapisany z edytora, a potem zmieniony w klasycznym formularzu: bloki są nieaktualne.
        $this->actingAs($this->admin())
            ->putJson(route('admin.podstrony.bloki.update', $page), $this->payload('Strona'))
            ->assertOk();
        $this->assertIsArray($page->fresh()->blocks);

        $page->fresh()->update(['content' => '<p>Zmienione w klasycznym edytorze</p>']);

        $this->assertNull($page->fresh()->blocks);
    }
}
