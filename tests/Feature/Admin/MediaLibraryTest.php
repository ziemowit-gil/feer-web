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

        return $this->storeFile();
    }

    /**
     * Adds a fake image to the library without re-faking the disk, so several
     * files can coexist in one test (Storage::fake resets the disk each call).
     */
    private function storeFile(string $name = 'photo.jpg'): Media
    {
        return MediaLibrary::instance()
            ->addMedia(UploadedFile::fake()->image($name))
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

    public function test_export_bundles_files_into_a_zip_mirroring_the_folder_tree(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $folder = MediaFolder::create(['name' => 'Wydarzenia']);
        $rootFile = $this->storeFile('root.jpg');
        $folderFile = $this->storeFile('event.jpg');
        $folderFile->update(['media_folder_id' => $folder->id]);

        $response = $this->actingAs($admin)->get(route('admin.multimedia.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');

        $entries = $this->zipEntries($response->getFile()->getPathname());

        $this->assertContains($rootFile->file_name, $entries);
        $this->assertContains('Wydarzenia/'.$folderFile->file_name, $entries);
    }

    public function test_export_scoped_to_a_folder_makes_that_folder_the_archive_root(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $parent = MediaFolder::create(['name' => 'Rok 2026']);
        $child = MediaFolder::create(['name' => 'Lipiec', 'parent_id' => $parent->id]);

        $parentFile = $this->storeFile('parent.jpg');
        $parentFile->update(['media_folder_id' => $parent->id]);
        $childFile = $this->storeFile('child.jpg');
        $childFile->update(['media_folder_id' => $child->id]);

        $response = $this->actingAs($admin)->get(route('admin.multimedia.export', ['folder' => $parent->id]));

        $response->assertOk();
        $entries = $this->zipEntries($response->getFile()->getPathname());

        // Relative to the scoped folder: parent file at the root, child nested.
        $this->assertContains($parentFile->file_name, $entries);
        $this->assertContains('Lipiec/'.$childFile->file_name, $entries);
    }

    public function test_export_with_no_files_returns_404(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->get(route('admin.multimedia.export'))
            ->assertNotFound();
    }

    public function test_import_recreates_files_and_folders_from_a_zip(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $zip = $this->makeZip([
            'raport.txt' => 'hello',
            'Zdjęcia/foto.jpg' => 'imagebytes',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.multimedia.import'), [
                'archive' => new UploadedFile($zip, 'paczka.zip', 'application/zip', null, true),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media', ['file_name' => 'raport.txt', 'model_type' => MediaLibrary::class]);

        $folder = MediaFolder::where('name', 'Zdjęcia')->first();
        $this->assertNotNull($folder);
        $this->assertDatabaseHas('media', ['file_name' => 'foto.jpg', 'media_folder_id' => $folder->id]);
    }

    public function test_import_targets_the_chosen_folder(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $target = MediaFolder::create(['name' => 'Docelowy']);

        $zip = $this->makeZip(['plik.pdf' => 'pdfbytes']);

        $this->actingAs($admin)
            ->post(route('admin.multimedia.import'), [
                'archive' => new UploadedFile($zip, 'paczka.zip', 'application/zip', null, true),
                'folder_id' => $target->id,
            ])
            ->assertRedirect(route('admin.multimedia.index', ['folder' => $target->id]));

        $this->assertDatabaseHas('media', ['file_name' => 'plik.pdf', 'media_folder_id' => $target->id]);
    }

    public function test_import_skips_executable_and_hidden_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $zip = $this->makeZip([
            'safe.jpg' => 'ok',
            'evil.php' => '<?php echo 1;',
            '__MACOSX/._safe.jpg' => 'junk',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.multimedia.import'), [
                'archive' => new UploadedFile($zip, 'paczka.zip', 'application/zip', null, true),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media', ['file_name' => 'safe.jpg']);
        $this->assertDatabaseMissing('media', ['file_name' => 'evil.php']);
        $this->assertSame(1, Media::count());
    }

    public function test_store_uploads_multiple_files_at_once(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $folder = MediaFolder::create(['name' => 'Wgrane']);

        $this->actingAs($admin)
            ->post(route('admin.multimedia.store'), [
                'folder_id' => $folder->id,
                'files' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                ],
            ])
            ->assertRedirect(route('admin.multimedia.index', ['folder' => $folder->id]));

        $this->assertSame(3, Media::count());
        $this->assertSame(3, Media::where('media_folder_id', $folder->id)->count());
    }

    public function test_index_offers_bulk_selection_and_multi_upload(): void
    {
        $this->uploadFile();

        $this->actingAs($this->admin())->get(route('admin.multimedia.index'))
            ->assertOk()
            ->assertSee('Zaznacz wszystkie na tej stronie')
            ->assertSee('Przeciągnij pliki tutaj lub kliknij, aby wybrać');
    }

    public function test_bulk_deletes_selected_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $a = $this->storeFile('a.jpg');
        $b = $this->storeFile('b.jpg');
        $c = $this->storeFile('c.jpg');

        $this->actingAs($admin)
            ->post(route('admin.multimedia.bulk'), [
                'action' => 'delete',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $a->id]);
        $this->assertDatabaseMissing('media', ['id' => $b->id]);
        $this->assertDatabaseHas('media', ['id' => $c->id]);
    }

    public function test_bulk_archives_and_restores_selected_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $a = $this->storeFile('a.jpg');
        $b = $this->storeFile('b.jpg');

        $this->actingAs($admin)
            ->post(route('admin.multimedia.bulk'), [
                'action' => 'archive',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertNotNull($a->fresh()->archived_at);
        $this->assertNotNull($b->fresh()->archived_at);

        $this->actingAs($admin)
            ->post(route('admin.multimedia.bulk'), [
                'action' => 'restore',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertNull($a->fresh()->archived_at);
        $this->assertNull($b->fresh()->archived_at);
    }

    public function test_bulk_moves_selected_files_to_a_folder(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $folder = MediaFolder::create(['name' => 'Cel']);
        $a = $this->storeFile('a.jpg');
        $b = $this->storeFile('b.jpg');

        $this->actingAs($admin)
            ->post(route('admin.multimedia.bulk'), [
                'action' => 'move',
                'ids' => [$a->id, $b->id],
                'folder_id' => $folder->id,
            ])
            ->assertRedirect();

        $this->assertSame($folder->id, $a->fresh()->media_folder_id);
        $this->assertSame($folder->id, $b->fresh()->media_folder_id);
    }

    public function test_export_selected_bundles_only_the_chosen_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $a = $this->storeFile('a.jpg');
        $b = $this->storeFile('b.jpg');
        $c = $this->storeFile('c.jpg');

        $response = $this->actingAs($admin)->post(route('admin.multimedia.export-selected'), [
            'ids' => [$a->id, $c->id],
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');

        $entries = $this->zipEntries($response->getFile()->getPathname());

        $this->assertContains($a->file_name, $entries);
        $this->assertContains($c->file_name, $entries);
        $this->assertNotContains($b->file_name, $entries);
    }

    /**
     * Reads the entry names out of a ZIP file on disk.
     */
    private function zipEntries(string $path): array
    {
        $zip = new \ZipArchive;
        $zip->open($path);

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->statIndex($i)['name'];
        }
        $zip->close();

        return $entries;
    }

    /**
     * Builds a temp ZIP file with the given [entry => contents] map.
     */
    private function makeZip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test-zip-').'.zip';
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }
}
