<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CronTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * System → Harmonogram (cron) i scripts/cron.sh na atrapie polecenia crontab (plik zamiast crona).
 */
class CronTabTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    private string $file;

    private string $appDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/crontest-'.uniqid();
        mkdir($this->dir.'/bin', 0777, true);
        $this->file = $this->dir.'/crontab.txt';
        file_put_contents($this->dir.'/bin/crontab', "#!/usr/bin/env bash\nF={$this->file}\nif [ \"\$1\" = \"-l\" ]; then [ -f \$F ] && cat \$F || { echo \"no crontab for user\" >&2; exit 1; }; elif [ \"\$1\" = \"-\" ]; then cat > \$F; else cp \"\$1\" \$F; fi\n");
        chmod($this->dir.'/bin/crontab', 0755);
        CronTab::$binary = $this->dir.'/bin/crontab';
        $this->appDir = realpath(base_path());
    }

    protected function tearDown(): void
    {
        CronTab::$binary = 'crontab';
        File::deleteDirectory($this->dir);
        File::deleteDirectory(CronTab::backupDir());
        parent::tearDown();
    }

    private function serverLikeCrontab(): void
    {
        $php = CronTab::phpBinary();
        file_put_contents($this->file, implode("\n", [
            'MAILTO=""',
            '* * * * * php -q /home/inna-domena/cron.php',
            "* * * * * cd {$this->appDir} && {$php} artisan schedule:run >> /dev/null 2>&1",
            "* * * * * php84 -q {$this->appDir}/artisan schedule:run >> {$this->appDir}/storage/logs/cron.log 2>&1",
        ])."\n");
    }

    public function test_diagnoza_wykrywa_martwy_wpis_i_duplikat(): void
    {
        $this->serverLikeCrontab();

        $a = CronTab::analyze(CronTab::read());

        $this->assertFalse($a['ok']);
        $this->assertCount(2, $a['entries']);
        $this->assertTrue($a['entries'][0]['ok']);
        $this->assertStringContainsString('„php84” bez pełnej ścieżki', $a['entries'][1]['problem']);
        $this->assertStringContainsString('Wpisów harmonogramu jest 2', implode(' ', $a['problems']));

        $this->assertStringContainsString('Brak wpisu harmonogramu', implode(' ', CronTab::analyze('MAILTO=""')['problems']));
    }

    public function test_walidacja_skladni(): void
    {
        $this->assertSame([], CronTab::validate("# komentarz\nMAILTO=\"\"\n\n*/15 3 * * 1-5 /usr/bin/php a.php\n@daily /bin/true"));
        $this->assertSame(['Linia 2: to nie jest poprawny wpis crona (5 pól czasu i polecenie).'], CronTab::validate("* * * * * ok\nrm -rf /"));
    }

    public function test_skrypt_naprawia_crontab_bez_ruszania_innych_wpisow_i_nie_dubluje(): void
    {
        $this->serverLikeCrontab();

        CronTab::repair();
        $after = file_get_contents($this->file);
        CronTab::repair();

        $this->assertSame($after, file_get_contents($this->file));
        $this->assertStringContainsString('php -q /home/inna-domena/cron.php', $after);
        $this->assertStringNotContainsString('php84 -q', $after);
        $this->assertSame(1, substr_count($after, 'schedule:run'));
        $this->assertTrue(CronTab::analyze($after)['ok']);
        $this->assertCount(1, CronTab::backups());
    }

    public function test_panel_diagnoza_zapis_z_potwierdzeniem_hasla_i_przywracanie(): void
    {
        $this->serverLikeCrontab();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]))->get(route('admin.cron.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.cron.index'))->assertOk()
            ->assertSee('Harmonogram (cron)')->assertSee('Napraw harmonogram')
            ->assertSee('<label for="cron-content"', false)->assertSee('php84 -q');

        // Konto z hasłem musi je potwierdzić.
        $this->actingAs($admin)->put(route('admin.cron.update'), ['content' => '* * * * * /bin/true'])->assertRedirect(route('password.confirm'));

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->from(route('admin.cron.index'))->put(route('admin.cron.update'), ['content' => "* * * * * ok\nzły wpis"])->assertSessionHasErrors('content');
        $this->from(route('admin.cron.index'))->put(route('admin.cron.update'), ['content' => "MAILTO=\"\"\n* * * * * /bin/true"])->assertSessionHasNoErrors();
        $this->assertSame("MAILTO=\"\"\n* * * * * /bin/true\n", file_get_contents($this->file));

        $backup = CronTab::backups()[0]['name'];
        $this->from(route('admin.cron.index'))->post(route('admin.cron.restore'), ['backup' => $backup])->assertSessionHasNoErrors();
        $this->assertStringContainsString('php84 -q', file_get_contents($this->file));
        $this->from(route('admin.cron.index'))->post(route('admin.cron.restore'), ['backup' => '../../.env'])->assertSessionHasErrors('cron');
    }

    public function test_konto_bez_hasla_sso_nie_musi_potwierdzac(): void
    {
        file_put_contents($this->file, "MAILTO=\"\"\n");
        $sso = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $sso->forceFill(['password' => null])->saveQuietly();

        $this->actingAs($sso)->from(route('admin.cron.index'))->post(route('admin.cron.repair'))->assertSessionHasNoErrors()->assertRedirect(route('admin.cron.index'));
        $this->assertSame(1, substr_count(file_get_contents($this->file), 'schedule:run'));
    }
}
