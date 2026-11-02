<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\BlockRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Edytor blokowy: zapis z edytora nie może wyzerować bloków tylko dlatego, że wyrenderowany HTML
 * różni się od zapisanego (np. po zmianie renderera) — bloki są wtedy nadal źródłem treści.
 * Zerowanie dotyczy wyłącznie zmiany `content` poza edytorem (formularz, edycja na żywo, przywrócenie wersji).
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

    /** @return array<string, array{string}> */
    public static function recordKinds(): array
    {
        return ['strona' => ['page'], 'aktualność' => ['news'], 'działanie' => ['project']];
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

    /** Rekord z blokami i z HTML-em, który NIE jest tym, co wyrenderuje bieżący renderer (jak po jego zmianie). */
    private function recordWithStaleHtml(string $kind): Model
    {
        $record = match ($kind) {
            'page' => Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>Stary HTML</p>', 'is_published' => true]),
            'news' => News::create(['title' => 'Aktualność', 'slug' => 'aktualnosc', 'content' => '<p>Stary HTML</p>', 'is_published' => true]),
            'project' => Project::create([
                'title' => 'Działanie', 'slug' => 'dzialanie', 'content' => '<p>Stary HTML</p>', 'is_published' => true,
                'category_id' => Category::firstOrCreate(['slug' => 'kategoria'], ['name' => 'Kategoria'])->id,
            ]),
        };
        $record->forceFill(['blocks' => $this->storedBlocks()])->saveQuietly();

        return $record->refresh();
    }

    private function saveFromEditor(Model $record, string $kind, string $title): void
    {
        $url = $kind === 'page'
            ? route('admin.podstrony.bloki.update', $record)
            : route('admin.edytor-blokowy.update', [$kind, $record->id]);

        $this->actingAs($this->admin())->putJson($url, $this->payload($title))->assertOk();
    }

    #[DataProvider('recordKinds')]
    public function test_save_from_the_editor_keeps_blocks_when_stored_html_is_stale(string $kind): void
    {
        $record = $this->recordWithStaleHtml($kind);

        $this->saveFromEditor($record, $kind, 'Nowy tytuł');

        $record->refresh();
        $this->assertSame('Nowy tytuł', $record->title);
        $this->assertSame(BlockRenderer::render($this->storedBlocks()), $record->content);
        $this->assertIsArray($record->blocks);
        $this->assertSame('Z bloków', $record->blocks[0]['attrs']['text']);
    }

    #[DataProvider('recordKinds')]
    public function test_content_changed_outside_the_editor_drops_blocks_even_on_the_instance_just_saved_from_the_editor(string $kind): void
    {
        $record = $this->recordWithStaleHtml($kind);

        $record->updateFromBlockEditor(['title' => 'Z edytora', 'blocks' => $this->storedBlocks(), 'content' => BlockRenderer::render($this->storedBlocks())]);
        $this->assertFalse($record->contentRenderedFromBlocks, 'Flaga zaufania do content nie może przeżyć zapisu.');
        $this->assertIsArray($record->fresh()->blocks);

        // Ta sama instancja, zwykły zapis treści (jak formularz klasyczny albo edycja na żywo): bloki są nieaktualne.
        $record->update(['content' => '<p>Zmienione poza edytorem</p>']);

        $this->assertNull($record->fresh()->blocks);
        $this->assertSame('<p>Zmienione poza edytorem</p>', $record->fresh()->content);
    }

    #[DataProvider('recordKinds')]
    public function test_flag_is_reset_after_a_failed_editor_save(string $kind): void
    {
        $record = $this->recordWithStaleHtml($kind);

        try {
            $record->updateFromBlockEditor(['title' => null, 'content' => '<p>x</p>']);
            $this->fail('Zapis bez tytułu powinien zakończyć się błędem bazy.');
        } catch (QueryException) {
            // Oczekiwane: kolumna `title` jest wymagana.
        }
        $this->assertFalse($record->contentRenderedFromBlocks, 'Flaga musi wrócić do false także po wyjątku.');

        $record->title = 'Poprawny tytuł';
        $record->update(['content' => '<p>Zmienione poza edytorem</p>']);

        $this->assertNull($record->fresh()->blocks);
    }

    #[DataProvider('recordKinds')]
    public function test_restoring_a_revision_with_different_content_drops_blocks(string $kind): void
    {
        $record = $this->recordWithStaleHtml($kind);
        $first = $record->revisions()->oldest('id')->firstOrFail();   // wersja początkowa: „Stary HTML”

        $record->updateFromBlockEditor(['blocks' => $this->storedBlocks(), 'content' => BlockRenderer::render($this->storedBlocks())]);
        $this->assertIsArray($record->fresh()->blocks);

        $record->fresh()->restoreRevision($first);

        $restored = $record->fresh();
        $this->assertSame('<p>Stary HTML</p>', $restored->content);
        $this->assertNull($restored->blocks, 'Przywrócona treść nie pochodzi z bloków, więc edytor ma ją wczytać jako HTML.');
    }
}
