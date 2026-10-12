<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Backup\BackupManager;
use App\Support\Backup\BackupSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        \Closure::bind(function () { static::$cached = null; }, null, SiteSetting::class)();

        $this->dir = sys_get_temp_dir().'/wecms-backup-test-'.uniqid();
        mkdir($this->dir.'/db', 0777, true);

        // Testy działają na bazie w pamięci — do kopii podpinamy osobną bazę-plik.
        $dbFile = $this->dir.'/db/test.sqlite';
        touch($dbFile);
        config([
            'backup.path' => $this->dir.'/archives',
            'backup.connections' => ['backup_test'],
            'database.connections.backup_test' => ['driver' => 'sqlite', 'database' => $dbFile, 'prefix' => '', 'foreign_key_constraints' => true],
        ]);
        DB::connection('backup_test')->statement('CREATE TABLE notes (id INTEGER PRIMARY KEY, body TEXT)');
        DB::connection('backup_test')->table('notes')->insert(['body' => 'oryginał']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('backup_test');
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_index_renders_for_admin_and_is_hidden_from_editors(): void
    {
        $this->actingAs($this->admin())->get(route('admin.kopie.index'))
            ->assertOk()->assertSee('Kopie zapasowe')->assertSee('Utwórz kopię');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]))
            ->get(route('admin.kopie.index'))->assertForbidden();
    }

    public function test_admin_creates_and_downloads_backup(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.kopie.store'), ['include_files' => '0'])
            ->assertSessionHasNoErrors();

        $backups = (new BackupManager)->list();
        $this->assertCount(1, $backups);

        $manifest = (new BackupManager)->readManifest($this->dir.'/archives/'.$backups[0]['name']);
        $this->assertSame('backup_test', $manifest['databases'][0]['connection']);
        $this->assertFalse($manifest['files']);

        $this->actingAs($admin)->get(route('admin.kopie.download', $backups[0]['name']))
            ->assertOk()->assertDownload($backups[0]['name']);
    }

    public function test_invalid_names_are_rejected(): void
    {
        $this->actingAs($this->admin())->get(route('admin.kopie.download', '..%2F.env'))->assertNotFound();
        $this->actingAs($this->admin())->get(route('admin.kopie.download', 'backup-2026-01-01-000000-manual.zip'))->assertNotFound();
    }

    public function test_restore_requires_confirmation_and_brings_data_back(): void
    {
        $manager = new BackupManager;
        $name = $manager->create('manual', false);

        DB::connection('backup_test')->table('notes')->update(['body' => 'zmienione']);

        $this->actingAs($this->admin())
            ->post(route('admin.kopie.restore', $name), ['confirmation' => 'tak'])
            ->assertSessionHasErrors('confirmation');
        $this->assertSame('zmienione', DB::connection('backup_test')->table('notes')->value('body'));

        $this->actingAs($this->admin())
            ->post(route('admin.kopie.restore', $name), ['confirmation' => 'PRZYWRÓĆ'])
            ->assertSessionHasNoErrors();

        $this->assertSame('oryginał', DB::connection('backup_test')->table('notes')->value('body'));
        $this->assertContains('pre-restore', array_column($manager->list(), 'kind'));
    }

    public function test_prune_keeps_configured_number_of_backups(): void
    {
        BackupSettings::load()->save(['keep' => 2]);
        $manager = new BackupManager;

        foreach (range(1, 3) as $i) {
            $name = $manager->create('manual', false);
            // Różne nazwy bez czekania: przesuwamy datę w nazwie.
            rename($this->dir.'/archives/'.$name, $this->dir.'/archives/backup-2026-01-0'.$i.'-000000-manual.zip');
        }

        $this->assertSame(1, (new BackupManager)->prune());
        $this->assertSame(
            ['backup-2026-01-03-000000-manual.zip', 'backup-2026-01-02-000000-manual.zip'],
            array_column((new BackupManager)->list(), 'name'),
        );
    }

    public function test_settings_are_saved_with_encrypted_password(): void
    {
        $this->actingAs($this->admin())->put(route('admin.kopie.settings'), [
            'schedule' => 'daily', 'time' => '03:15', 'keep' => 5, 'include_files' => '1',
            'remote_type' => 'sftp', 'remote_host' => 'backup.example.org', 'remote_username' => 'feer',
            'remote_password' => 'tajne-haslo', 'remote_root' => '/kopie', 'remote_keep' => 10,
        ])->assertSessionHasNoErrors();

        $raw = file_get_contents(BackupSettings::path());
        $this->assertStringNotContainsString('tajne-haslo', $raw);

        $settings = BackupSettings::load();
        $this->assertSame('daily', $settings->get('schedule'));
        $this->assertSame('tajne-haslo', $settings->remotePassword());
        $this->assertSame('sftp', $settings->remoteDiskConfig()['driver']);

        // Puste hasło przy kolejnym zapisie nie kasuje zapisanego.
        $this->actingAs($this->admin())->put(route('admin.kopie.settings'), [
            'schedule' => 'off', 'time' => '03:15', 'keep' => 5,
            'remote_type' => 'sftp', 'remote_host' => 'backup.example.org', 'remote_username' => 'feer',
            'remote_password' => '', 'remote_keep' => 10,
        ])->assertSessionHasNoErrors();
        $this->assertSame('tajne-haslo', BackupSettings::load()->remotePassword());
    }

    public function test_upload_rejects_foreign_zip(): void
    {
        $zipPath = $this->dir.'/obcy.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('readme.txt', 'nie kopia');
        $zip->close();

        $this->actingAs($this->admin())->post(route('admin.kopie.upload'), [
            'archive' => new UploadedFile($zipPath, 'obcy.zip', 'application/zip', null, true),
        ])->assertSessionHasErrors('archive');

        $this->assertSame([], (new BackupManager)->list());
    }
}
