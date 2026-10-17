<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Backup\BackupManager;
use App\Support\HealthDashboard;
use App\Support\LogDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** System → Kondycja serwisu: sekcje pulpitu (harmonogram, kolejka, deploy, kopie, baza, błędy z doby). */
class HealthDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/health-'.uniqid();
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_strona_dla_admina_ma_wszystkie_sekcje(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $html = $this->actingAs($admin)->get(route('admin.health.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<h1 class="text-lg font-bold text-ink">Kondycja serwisu</h1>', $html);
        foreach (['Kondycja serwisu', 'Harmonogram', 'Kolejka zadań', 'Ostatnie wdrożenie', 'Kopie zapasowe', 'Baza danych', 'Błędy z ostatniej doby', 'Wdrożenie'] as $title) {
            $this->assertStringContainsString($title, $html);
        }
        $this->assertStringContainsString('aria-labelledby="sec-errors"', $html);
        $this->assertStringContainsString('Wersja CMS <strong class="text-ink" data-cms-version>'.config('cms.version').'</strong>', $html);
        $this->assertMatchesRegularExpression('/^\d+\.\d+(\.\d+)?$/', (string) config('cms.version'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]))->get(route('admin.health.index'))->assertForbidden();
    }

    public function test_log_digest_grupuje_bledy_z_ostatniej_doby(): void
    {
        $ts = fn ($h) => now()->subHours($h)->format('Y-m-d H:i:s');
        $log = $this->dir.'/laravel.log';
        file_put_contents($log, implode("\n", [
            "[{$ts(30)}] local.ERROR: Stary błąd 42 {\"exception\":\"x\"}",
            "[{$ts(5)}] local.ERROR: SQLSTATE[23000]: Integrity constraint violation: 1452 user_id 17 {\"userId\":1,\"exception\":\"[object] (PDOException)\"}",
            '#0 /home/srv/feer-web/vendor/laravel/framework/src/Illuminate/Database/Connection.php(626): x',
            '#13 /home/srv/feer-web/app/Models/Concerns/HasRevisions.php(49): Illuminate\\Database\\x',
            "[{$ts(3)}] local.ERROR: SQLSTATE[23000]: Integrity constraint violation: 1452 user_id 23 {\"exception\":\"y\"}",
            "[{$ts(2)}] local.WARNING: Ostrzeżenie się nie liczy",
            "[{$ts(1)}] production.CRITICAL: unserialize(): Error at offset 0 of 152 bytes",
        ])."\n");

        $d = LogDigest::read($log);

        $this->assertSame(3, $d['total']);
        $this->assertCount(2, $d['groups']);
        $this->assertSame(2, $d['groups'][0]['count']);
        $this->assertSame('SQLSTATE[#]: Integrity constraint violation: # user_id #', $d['groups'][0]['message']);
        $this->assertSame('app/Models/Concerns/HasRevisions.php:49', $d['groups'][0]['frame']);
        $this->assertSame('CRITICAL', $d['groups'][1]['level']);
    }

    public function test_log_digest_czyta_tylko_koniec_pliku(): void
    {
        $log = $this->dir.'/laravel.log';
        $ts = now()->subHour()->format('Y-m-d H:i:s');
        file_put_contents($log, "[{$ts}] local.ERROR: Na początku pliku\n".str_repeat("[{$ts}] local.INFO: wypełniacz\n", 5000)."[{$ts}] local.ERROR: Na końcu pliku\n");

        $d = LogDigest::read($log, 24, 10, 4096);

        $this->assertTrue($d['truncated']);
        $this->assertSame(['Na końcu pliku'], array_column($d['groups'], 'message'));
    }

    public function test_parsowanie_logu_wdrozenia(): void
    {
        $ok = $this->dir.'/deploy-20261017-170524.log';
        file_put_contents($ok, "  10/10  Kontrola końcowa\n\e[0;32m✓ Wdrożenie zakończone\e[0m — a39d41c w 2 min 41 s\n");
        $fail = $this->dir.'/deploy-20261017-162921.log';
        file_put_contents($fail, "  ✗ Wdrożenie NIEUDANE — krok 6/10 „Budowanie assetów (npm)” (kod 1, 1 min 2 s)\n");

        $a = HealthDashboard::parseDeployLog($ok);
        $this->assertSame(['ok', 'a39d41c', '2 min 41 s'], [$a['result'], $a['commit'], $a['duration']]);
        $this->assertSame('2026-10-17 17:05', $a['at']->format('Y-m-d H:i'));

        $b = HealthDashboard::parseDeployLog($fail);
        $this->assertSame(['fail', 'Budowanie assetów (npm)'], [$b['result'], $b['failed_step']]);

        // Sekcja bierze najnowszy log.
        $section = (new HealthDashboard)->deploy($this->dir);
        $this->assertSame('ok', $section['status']);
        $this->assertSame('a39d41c', $section['rows']['Wdrożony commit']);
    }

    public function test_kopie_ostrzezenie_bez_kopii_i_przy_starej(): void
    {
        $manager = \Mockery::mock(BackupManager::class);
        $manager->shouldReceive('list')->andReturn([]);
        $empty = (new HealthDashboard)->backups($manager);
        $this->assertSame('warning', $empty['status']);
        $this->assertSame('Brak kopii zapasowych', $empty['summary']);

        $old = \Mockery::mock(BackupManager::class);
        $old->shouldReceive('list')->andReturn([['name' => 'b.zip', 'kind' => 'auto', 'kind_label' => 'automatyczna', 'size' => 2048, 'created_at' => now()->subDays(3)]]);
        $this->assertSame('warning', (new HealthDashboard)->backups($old)['status']);

        $fresh = \Mockery::mock(BackupManager::class);
        $fresh->shouldReceive('list')->andReturn([['name' => 'b.zip', 'kind' => 'auto', 'kind_label' => 'automatyczna', 'size' => 2048, 'created_at' => now()->subHours(3)]]);
        $section = (new HealthDashboard)->backups($fresh);
        $this->assertSame('ok', $section['status']);
        $this->assertStringContainsString('2,0 KB', $section['rows']['Ostatnia']);
    }

    public function test_bledy_ostrzegaja_i_podpowiadaja_rotacje_duzego_logu(): void
    {
        $log = $this->dir.'/laravel.log';
        file_put_contents($log, '['.now()->subMinutes(5)->format('Y-m-d H:i:s')."] local.ERROR: Coś poszło nie tak\n");

        $section = (new HealthDashboard)->errors($log);

        $this->assertSame('warning', $section['status']);
        $this->assertSame('1 błąd w 1 rodzaju', $section['summary']);
        $this->assertSame('Coś poszło nie tak', $section['table']['rows'][0][0]);
    }

    public function test_wyczysc_log_archiwizuje_i_oproznia(): void
    {
        $log = $this->dir.'/laravel.log';
        file_put_contents($log, '['.now()->format('Y-m-d H:i:s')."] local.ERROR: Błąd do wyczyszczenia\n");
        for ($i = 0; $i < 7; $i++) {
            touch($this->dir.'/laravel-archiwum-2026010'.$i.'-000000.log.gz');
        }

        $r = LogDigest::clear($log);

        $this->assertSame('', file_get_contents($log));
        $this->assertStringContainsString('Błąd do wyczyszczenia', gzdecode(file_get_contents($r['archive'])));
        $this->assertCount(LogDigest::KEEP_ARCHIVES, glob($this->dir.'/laravel-archiwum-*.log.gz'));
        $this->assertSame(0, LogDigest::read($log)['total']);
        $this->assertSame(['archive' => null, 'bytes' => 0], LogDigest::clear($log));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('admin.health.index'))->assertOk()
            ->assertSee(route('admin.health.clear-log'), false)->assertSee('Wyczyść log');
    }
}
