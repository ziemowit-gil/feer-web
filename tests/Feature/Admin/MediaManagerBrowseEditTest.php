<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaLibrary;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Biblioteka plików w duchu menedżera mediów Joomli: foldery jako kafle, sortowanie
 * (zapamiętane w sesji), menu akcji, podgląd, upuszczanie plików i edytor obrazu
 * (kadr/obrót/rozmiar zapisane jako nowa wersja albo kopia).
 */
class MediaManagerBrowseEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function addFile(UploadedFile $file, ?int $folderId = null): Media
    {
        /** @var Media $media */
        $media = MediaLibrary::instance()->addMedia($file)->toMediaCollection('files');
        if ($folderId) {
            $media->update(['media_folder_id' => $folderId]);
        }

        return $media->fresh();
    }

    private function textFile(string $name, int $bytes): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($path, str_repeat('a', $bytes));

        return new UploadedFile($path, $name, 'text/plain', null, true);
    }

    public function test_subfolders_are_shown_as_tiles_with_a_way_up(): void
    {
        $parent = MediaFolder::create(['name' => 'Wydarzenia']);
        $child = MediaFolder::create(['name' => 'Gala 2026', 'parent_id' => $parent->id]);
        MediaFolder::create(['name' => 'Kulisy', 'parent_id' => $child->id]);
        $this->addFile(UploadedFile::fake()->image('scena.jpg'), $child->id);

        $response = $this->actingAs($this->admin())->get(route('admin.multimedia.index', ['folder' => $parent->id]));

        $response->assertOk()
            ->assertSee('aria-label="Foldery"', false)
            ->assertSee('W górę')
            ->assertSee('do: Wszystkie pliki')
            ->assertSee(route('admin.multimedia.index', ['folder' => $child->id]), false)
            ->assertSee('1 plik · 1 podfolder')
            ->assertSee('W tym miejscu nie ma plików');

        // Przy wyszukiwaniu (cała biblioteka) kafli folderów nie ma.
        $this->get(route('admin.multimedia.index', ['folder' => $parent->id, 'q' => 'scena']))
            ->assertOk()->assertDontSee('W górę');
    }

    public function test_files_can_be_sorted_by_name_and_size_and_the_choice_is_remembered(): void
    {
        $this->addFile($this->textFile('mango.txt', 300));
        $this->addFile($this->textFile('zebra.txt', 10));
        $this->addFile($this->textFile('alfa.txt', 2000));
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.multimedia.index', ['sort' => 'nazwa']))
            ->assertOk()->assertSeeInOrder(['alfa.txt', 'mango.txt', 'zebra.txt'])
            ->assertSee('Nazwa A–Ż');

        // Bez parametru — ostatni wybór z sesji.
        $this->actingAs($admin)->get(route('admin.multimedia.index'))
            ->assertSeeInOrder(['alfa.txt', 'mango.txt', 'zebra.txt']);

        $this->actingAs($admin)->get(route('admin.multimedia.index', ['sort' => 'najwieksze']))
            ->assertSeeInOrder(['alfa.txt', 'mango.txt', 'zebra.txt']);
        $this->actingAs($admin)->get(route('admin.multimedia.index', ['sort' => 'najmniejsze']))
            ->assertSeeInOrder(['zebra.txt', 'mango.txt', 'alfa.txt']);
        $this->actingAs($admin)->get(route('admin.multimedia.index', ['sort' => 'nazwa-malejaco']))
            ->assertSeeInOrder(['zebra.txt', 'mango.txt', 'alfa.txt']);

        // Nieznana wartość nie psuje listy (zostaje zapamiętana).
        $this->actingAs($admin)->get(route('admin.multimedia.index', ['sort' => 'cokolwiek']))
            ->assertOk()->assertSeeInOrder(['zebra.txt', 'mango.txt', 'alfa.txt']);
    }

    public function test_sorting_by_name_uses_the_display_name_after_renaming(): void
    {
        $first = $this->addFile($this->textFile('b-plik.txt', 10));
        $this->addFile($this->textFile('c-plik.txt', 10));
        $first->setCustomProperty('display_name', 'Wykaz zbiorczy 2026')->save();

        $this->actingAs($this->admin())->get(route('admin.multimedia.index', ['sort' => 'nazwa']))
            ->assertOk()->assertSeeInOrder(['c-plik.txt', 'Wykaz zbiorczy 2026']);
    }

    public function test_tiles_offer_actions_preview_and_image_editing_only_for_editable_images(): void
    {
        $photo = $this->addFile(UploadedFile::fake()->image('plakat.png', 40, 30));
        $svgPath = tempnam(sys_get_temp_dir(), 'svg');
        file_put_contents($svgPath, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>');
        $svg = $this->addFile(new UploadedFile($svgPath, 'logo.svg', 'image/svg+xml', null, true));
        $this->assertTrue($svg->isImage());
        $doc = $this->addFile($this->textFile('regulamin.txt', 50));

        $response = $this->actingAs($this->admin())->get(route('admin.multimedia.index'));

        $response->assertOk()
            ->assertSee('Akcje: plakat.png')
            ->assertSee('Akcje: regulamin.txt')
            ->assertSee('Podgląd')
            ->assertSee('Mniejsze miniatury')
            ->assertSee('Upuść pliki, aby wgrać je do katalogu głównego')
            ->assertSee('media-preview-title', false)
            ->assertSee('Edycja obrazu', false);

        // Adresy zapisu edytora są w danych Alpine (JSON z ucieczkami) — porównujemy bez ukośników wstecznych.
        $html = str_replace('\\', '', $response->getContent());
        $this->assertStringContainsString(route('admin.multimedia.edit-image', $photo), $html);
        $this->assertStringNotContainsString(route('admin.multimedia.edit-image', $svg), $html);
        $this->assertStringNotContainsString(route('admin.multimedia.edit-image', $doc), $html);

        // Szuflada szczegółów: „Edytuj obraz” tylko dla JPG/PNG/WEBP.
        $this->get(route('admin.multimedia.panel', $photo))->assertOk()->assertSee('Edytuj obraz');
        $this->get(route('admin.multimedia.panel', $doc))->assertOk()->assertDontSee('Edytuj obraz');
    }

    public function test_editor_source_streams_the_original_image_from_the_panel(): void
    {
        $photo = $this->addFile(UploadedFile::fake()->image('plakat.png', 40, 30));
        $doc = $this->addFile($this->textFile('regulamin.txt', 50));
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.multimedia.source', $photo))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($admin)->get(route('admin.multimedia.source', $doc))->assertNotFound();
    }

    public function test_edited_image_saved_as_new_version_keeps_the_url(): void
    {
        $photo = $this->addFile(UploadedFile::fake()->image('plakat.png', 40, 30));
        $url = $photo->getUrl();

        $response = $this->actingAs($this->admin())->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'version',
            'note' => 'Kadr 1:1',
            'file' => UploadedFile::fake()->image('plakat.png', 20, 20),
        ]);

        $response->assertOk()
            ->assertJson(['id' => $photo->id, 'redirect' => route('admin.multimedia.index')])
            ->assertSessionHas('media_panel', $photo->id);
        $photo->refresh();
        $this->assertSame($url, $photo->getUrl());
        $this->assertSame([20, 20], array_slice(getimagesize($photo->getPath()), 0, 2));
        $this->assertSame(1, $photo->versions()->count());
        $this->assertSame('Kadr 1:1', $photo->versions()->first()->note);
    }

    public function test_edited_image_can_be_saved_as_a_copy_in_the_same_folder(): void
    {
        $folder = MediaFolder::create(['name' => 'Plakaty']);
        $photo = $this->addFile(UploadedFile::fake()->image('plakat.png', 40, 30), $folder->id);
        $photo->setCustomProperty('alt', 'Plakat koncertu')->setCustomProperty('tags', ['koncert'])->save();

        $response = $this->actingAs($this->admin())->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'copy',
            'file' => UploadedFile::fake()->image('plakat.png', 16, 9),
        ]);

        $response->assertOk();
        $copy = Media::findOrFail($response->json('id'));
        $this->assertNotSame($photo->id, $copy->id);
        $this->assertSame('plakat-edycja.png', $copy->file_name);
        $this->assertSame($folder->id, $copy->media_folder_id);
        $this->assertSame('Plakat koncertu', $copy->getCustomProperty('alt'));
        $this->assertSame(['koncert'], $copy->getCustomProperty('tags'));
        $this->assertSame([16, 9], array_slice(getimagesize($copy->getPath()), 0, 2));
        $this->assertSame([40, 30], array_slice(getimagesize($photo->fresh()->getPath()), 0, 2));
        $this->assertSame(0, $photo->versions()->count());
        $response->assertJson(['redirect' => route('admin.multimedia.index', ['folder' => $folder->id])]);
    }

    public function test_editor_rejects_a_different_format_and_non_editable_files(): void
    {
        $photo = $this->addFile(UploadedFile::fake()->image('plakat.png', 40, 30));
        $doc = $this->addFile($this->textFile('regulamin.txt', 50));
        $admin = $this->admin();

        // Przeglądarka oddała JPG zamiast PNG (np. brak obsługi formatu) — nie podmieniamy.
        $this->actingAs($admin)->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'version',
            'file' => UploadedFile::fake()->image('plakat.png', 20, 20)->mimeType('image/jpeg'),
        ])->assertUnprocessable();

        $this->actingAs($admin)->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'version',
            'file' => UploadedFile::fake()->image('inny.jpg', 20, 20),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->actingAs($admin)->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'nadpisz',
            'file' => UploadedFile::fake()->image('plakat.png', 20, 20),
        ])->assertUnprocessable()->assertJsonValidationErrors('mode');

        $this->actingAs($admin)->postJson(route('admin.multimedia.edit-image', $doc), [
            'mode' => 'version',
            'file' => UploadedFile::fake()->image('regulamin.png', 20, 20),
        ])->assertUnprocessable();

        $this->assertSame(0, $photo->versions()->count());
        $this->assertSame([40, 30], array_slice(getimagesize($photo->fresh()->getPath()), 0, 2));
    }

    public function test_editor_respects_folder_permissions(): void
    {
        $allowed = UserGroup::create(['name' => 'Szkolenia', 'modules' => []]);
        $other = UserGroup::create(['name' => 'PR', 'modules' => []]);
        $folder = MediaFolder::create(['name' => 'Wewnętrzne']);
        $folder->userGroups()->sync([$allowed->id]);
        $photo = $this->addFile(UploadedFile::fake()->image('tajne.png', 40, 30), $folder->id);
        $outsider = User::factory()->create(['role' => User::ROLE_EDITOR, 'user_group_id' => $other->id]);

        $this->actingAs($outsider)->get(route('admin.multimedia.source', $photo))->assertForbidden();
        $this->actingAs($outsider)->postJson(route('admin.multimedia.edit-image', $photo), [
            'mode' => 'copy',
            'file' => UploadedFile::fake()->image('tajne.png', 20, 20),
        ])->assertForbidden();

        $this->assertSame(1, Media::count());
    }
}
