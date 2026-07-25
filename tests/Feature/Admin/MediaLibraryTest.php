<?php

namespace Tests\Feature\Admin;

use App\Models\MediaFolder;
use App\Models\MediaLibrary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function uploadFile(): Media
    {
        Storage::fake('public');

        return MediaLibrary::instance()
            ->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->toMediaCollection('files');
    }

    public function test_index_shows_folder_tree_and_archive_controls(): void
    {
        MediaFolder::create(['name' => 'Zdjęcia z wydarzeń']);
        $this->uploadFile();

        $response = $this->actingAs($this->admin())->get(route('admin.multimedia.index'));

        $response->assertOk();
        $response->assertSee('Foldery');
        $response->assertSee('Zdjęcia z wydarzeń');
        $response->assertSee('Pokaż archiwum');
        $response->assertSee('Schowaj / archiwizuj');
    }

    public function test_a_file_can_be_archived_and_hidden_from_the_active_view(): void
    {
        $admin = $this->admin();
        $media = $this->uploadFile();

        $this->actingAs($admin)
            ->put(route('admin.multimedia.archive', $media))
            ->assertRedirect();

        $this->assertNotNull($media->fresh()->archived_at);

        // Hidden from the default (active) library view...
        $this->actingAs($admin)->get(route('admin.multimedia.index'))
            ->assertDontSee($media->file_name);

        // ...but visible in the archive view.
        $this->actingAs($admin)->get(route('admin.multimedia.index', ['archived' => 1]))
            ->assertSee($media->file_name)
            ->assertSee('Przywróć z archiwum');
    }

    public function test_an_archived_file_can_be_restored(): void
    {
        $admin = $this->admin();
        $media = $this->uploadFile();
        $media->update(['archived_at' => now()]);

        $this->actingAs($admin)
            ->put(route('admin.multimedia.restore', $media))
            ->assertRedirect();

        $this->assertNull($media->fresh()->archived_at);

        $this->actingAs($admin)->get(route('admin.multimedia.index'))
            ->assertSee($media->file_name);
    }

    public function test_archived_images_are_excluded_from_the_editor_picker(): void
    {
        $admin = $this->admin();
        $media = $this->uploadFile();
        $media->update(['archived_at' => now()]);

        $this->actingAs($admin)->getJson(route('admin.multimedia.images'))
            ->assertOk()
            ->assertJsonMissing(['id' => $media->id]);
    }

    public function test_index_offers_grid_and_list_views(): void
    {
        $this->uploadFile();

        $this->actingAs($this->admin())->get(route('admin.multimedia.index'))
            ->assertOk()
            ->assertSee('Widok kafelków')
            ->assertSee('Widok listy');
    }

    public function test_deleting_a_folder_moves_its_contents_up_to_the_parent(): void
    {
        $admin = $this->admin();

        $parent = MediaFolder::create(['name' => 'Rok 2026']);
        $child = MediaFolder::create(['name' => 'Lipiec', 'parent_id' => $parent->id]);
        $grandchild = MediaFolder::create(['name' => 'Wydarzenie', 'parent_id' => $child->id]);

        $media = $this->uploadFile();
        $media->update(['media_folder_id' => $child->id]);

        $this->actingAs($admin)
            ->delete(route('admin.multimedia.foldery.destroy', $child))
            ->assertRedirect();

        // The folder is gone...
        $this->assertDatabaseMissing('media_folders', ['id' => $child->id]);

        // ...its file moved up to the parent (not deleted)...
        $this->assertNotNull($media->fresh());
        $this->assertSame($parent->id, $media->fresh()->media_folder_id);

        // ...and its subfolder was re-parented to the grandparent.
        $this->assertSame($parent->id, $grandchild->fresh()->parent_id);
    }

    public function test_deleting_a_root_folder_moves_its_contents_to_the_root(): void
    {
        $admin = $this->admin();

        $folder = MediaFolder::create(['name' => 'Do usunięcia']);
        $media = $this->uploadFile();
        $media->update(['media_folder_id' => $folder->id]);

        $this->actingAs($admin)
            ->delete(route('admin.multimedia.foldery.destroy', $folder))
            ->assertRedirect();

        $this->assertDatabaseMissing('media_folders', ['id' => $folder->id]);
        $this->assertNull($media->fresh()->media_folder_id);
    }
}
