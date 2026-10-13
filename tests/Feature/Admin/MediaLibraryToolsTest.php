<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Media\DocumentA11yChecker;
use App\Services\Media\MediaUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MediaLibraryToolsTest extends TestCase
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

    private function editorIn(UserGroup $group): User
    {
        return User::factory()->create(['role' => User::ROLE_EDITOR, 'user_group_id' => $group->id]);
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

    private function pdf(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, "%PDF-1.7\n" . $body . "\n%%EOF");

        return new UploadedFile($path, 'dokument.pdf', 'application/pdf', null, true);
    }

    private function docx(array $parts): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx') . '.docx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        foreach ($parts as $name => $xml) {
            $zip->addFromString($name, $xml);
        }
        $zip->close();

        return new UploadedFile($path, 'dokument.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    public function test_replacing_a_file_keeps_its_url_and_stores_the_previous_version(): void
    {
        $admin = $this->admin();
        $media = $this->addFile($this->pdf('/Title (Stary)'));
        $url = $media->getUrl();

        $this->actingAs($admin)
            ->post(route('admin.multimedia.replace', $media), [
                'file' => $this->pdf('/Title (Nowy regulamin)'),
                'note' => 'Regulamin od listopada',
            ])
            ->assertRedirect()
            ->assertSessionHas('media_panel', $media->id);

        $media->refresh();
        $this->assertSame($url, $media->getUrl());
        $this->assertStringContainsString('Nowy regulamin', file_get_contents($media->getPath()));

        $version = $media->versions()->first();
        $this->assertNotNull($version);
        $this->assertSame('Regulamin od listopada', $version->note);
        $this->assertStringContainsString('Stary', file_get_contents($version->absolutePath()));

        // Przywrócenie: stara treść wraca pod ten sam adres, bieżąca trafia do historii.
        $this->actingAs($admin)->post(route('admin.multimedia.versions.restore', [$media, $version]))->assertRedirect();
        $this->assertStringContainsString('Stary', file_get_contents($media->fresh()->getPath()));
        $this->assertSame(2, $media->versions()->count());

        $this->actingAs($admin)->get(route('admin.multimedia.versions.download', [$media, $version]))->assertOk();
    }

    public function test_replacement_must_keep_the_file_extension(): void
    {
        $media = $this->addFile($this->pdf('/Title (A)'));

        $this->actingAs($this->admin())
            ->post(route('admin.multimedia.replace', $media), ['file' => UploadedFile::fake()->image('obraz.png')])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $media->versions()->count());
    }

    public function test_usage_is_found_in_content_and_json_and_blocks_deleting_without_confirmation(): void
    {
        $admin = $this->admin();
        $media = $this->addFile(UploadedFile::fake()->image('foto.jpg'));
        $other = $this->addFile(UploadedFile::fake()->image('inne.jpg'));

        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'is_published' => true,
            'content' => '<p><img src="/storage/' . $media->id . '/foto.jpg" alt="Zespół"></p>']);
        Page::create(['title' => 'Bloki', 'slug' => 'bloki', 'is_published' => true, 'content' => '<p>x</p>',
            'blocks' => [['type' => 'image', 'attrs' => ['src' => '/storage/' . $other->id . '/inne.jpg']]]]);

        $usage = app(MediaUsage::class);
        $this->assertSame(['O nas'], array_column($usage->forMedia($media), 'title'));
        $this->assertSame(['Bloki'], array_column($usage->forMedia($other), 'title'));
        $this->assertSame(1, $usage->counts(true)[$media->id] ?? 0);

        $this->actingAs($admin)->get(route('admin.multimedia.panel', $media))
            ->assertOk()->assertSee('Gdzie użyto')->assertSee('O nas');

        // Usunięcie pliku w użyciu: najpierw lista miejsc i prośba o potwierdzenie.
        $this->actingAs($admin)->delete(route('admin.multimedia.destroy', $media))
            ->assertRedirect()->assertSessionHas('usage_confirm');
        $this->assertNotNull(Media::find($media->id));

        $this->actingAs($admin)->post(route('admin.multimedia.bulk'), ['action' => 'delete', 'ids' => [$media->id], 'force' => 1])
            ->assertRedirect();
        $this->assertNull(Media::find($media->id));
    }

    public function test_restricted_folder_is_visible_only_to_admins_and_allowed_groups(): void
    {
        $training = UserGroup::create(['name' => 'Szkolenia', 'modules' => []]);
        $pr = UserGroup::create(['name' => 'PR', 'modules' => []]);
        $folder = MediaFolder::create(['name' => 'Szkolenia — materiały']);
        $sub = MediaFolder::create(['name' => 'Wewnętrzne', 'parent_id' => $folder->id]);
        $file = $this->addFile(UploadedFile::fake()->image('tajne.jpg'), $sub->id);

        $this->actingAs($this->admin())
            ->put(route('admin.multimedia.foldery.permissions', $folder), ['groups' => [$training->id]])
            ->assertRedirect();
        $this->flushSession(); // komunikat o zmianie dostępu zawiera nazwę folderu

        $outsider = $this->editorIn($pr);
        $this->actingAs($outsider)->get(route('admin.multimedia.index'))
            ->assertOk()->assertDontSee('Szkolenia — materiały');
        $this->actingAs($outsider)->get(route('admin.multimedia.index', ['folder' => $sub->id]))->assertForbidden();
        $this->actingAs($outsider)->get(route('admin.multimedia.index', ['q' => 'tajne']))->assertDontSee('tajne.jpg');
        $this->actingAs($outsider)->get(route('admin.multimedia.panel', $file))->assertForbidden();
        $this->actingAs($outsider)->put(route('admin.multimedia.foldery.permissions', $folder), ['groups' => []])->assertForbidden();

        $member = $this->editorIn($training);
        $this->actingAs($member)->get(route('admin.multimedia.index', ['folder' => $sub->id]))
            ->assertOk()->assertSee('tajne.jpg');
    }

    public function test_cleanup_reports_duplicates_and_unused_files(): void
    {
        $image = UploadedFile::fake()->image('a.jpg', 50, 50);
        $copyPath = tempnam(sys_get_temp_dir(), 'img');
        copy($image->getRealPath(), $copyPath);

        $first = $this->addFile($image);
        $this->addFile(new UploadedFile($copyPath, 'kopia.jpg', 'image/jpeg', null, true));
        Page::create(['title' => 'Strona', 'slug' => 'strona', 'is_published' => true,
            'content' => '<img src="/storage/' . $first->id . '/a.jpg" alt="">']);

        $this->actingAs($this->admin())->get(route('admin.multimedia.cleanup'))
            ->assertOk()
            ->assertSee('Duplikaty')
            ->assertSee('2 kopie')
            ->assertSee('kopia.jpg');

        $this->assertNotNull($first->fresh()->sha1);
    }

    public function test_document_accessibility_checks_pdf_and_docx(): void
    {
        $checker = app(DocumentA11yChecker::class);

        $good = $this->addFile($this->pdf('1 0 obj << /Type /Catalog /Lang (pl-PL) /MarkInfo << /Marked true >> /StructTreeRoot 5 0 R >> endobj 2 0 obj << /Title (Regulamin) >> endobj /Font << /F1 3 0 R >>'));
        $report = $checker->check($good);
        $this->assertSame('warn', $report['status']); // brak tylko zalecanego DisplayDocTitle
        $this->assertTrue(collect($report['checks'])->firstWhere('key', 'tagged')['ok']);

        $scan = $this->addFile($this->pdf('1 0 obj << /Type /Catalog >> endobj'));
        $report = $checker->check($scan);
        $this->assertSame('fail', $report['status']);
        $this->assertFalse(collect($report['checks'])->firstWhere('key', 'lang')['ok']);
        $this->assertFalse(collect($report['checks'])->firstWhere('key', 'text')['ok']);

        $docx = $this->addFile($this->docx([
            'docProps/core.xml' => '<cp:coreProperties xmlns:cp="x" xmlns:dc="y"><dc:title>Sprawozdanie</dc:title></cp:coreProperties>',
            'word/styles.xml' => '<w:styles xmlns:w="w"><w:lang w:val="pl-PL"/></w:styles>',
            'word/document.xml' => '<w:document xmlns:w="w" xmlns:wp="wp"><w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr></w:p><wp:docPr id="1" name="Obraz 1"/></w:document>',
        ]));
        $report = $checker->check($docx);
        $this->assertSame('fail', $report['status']);
        $this->assertTrue(collect($report['checks'])->firstWhere('key', 'title')['ok']);
        $this->assertFalse(collect($report['checks'])->firstWhere('key', 'alt')['ok']);

        $this->actingAs($this->admin())->get(route('admin.multimedia.documents'))
            ->assertOk()
            ->assertSee('Dostępność dokumentów')
            ->assertSee('Wymaga poprawy');
    }
}
