<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Automatyczne pobieranie kodu po pushu (cli/autopull.sh, cron co minutę na produkcji): na tymczasowych
 * repozytoriach git — „origin” (bare), kopia „serwera” ze skryptem i kopia, z której wypychamy commity.
 * Zamiast PHP skrypt dostaje atrapę, która zapisuje wywołania (artisan optimize).
 */
class AutopullScriptTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        if (! trim((string) shell_exec('command -v bash')) || ! trim((string) shell_exec('command -v git'))) {
            $this->markTestSkipped('Brak basha albo gita.');
        }
        $this->dir = sys_get_temp_dir().'/autopull-test-'.bin2hex(random_bytes(4));
        File::makeDirectory($this->dir);

        $this->sh('git init -q --bare -b main origin.git');
        $this->sh('git clone -q origin.git pusher');
        $this->commit('README.md', "start\n", 'start');
        $this->sh('git clone -q origin.git server');
        File::makeDirectory($this->dir.'/server/cli');
        File::copy(base_path('cli/autopull.sh'), $this->dir.'/server/cli/autopull.sh');
        File::put($this->dir.'/php-stub', "#!/usr/bin/env bash\necho \"\$*\" >> ".escapeshellarg($this->dir.'/php-calls.txt')."\n");
        chmod($this->dir.'/php-stub', 0755);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function sh(string $cmd, string $cwd = ''): string
    {
        $env = 'GIT_AUTHOR_NAME=Test GIT_AUTHOR_EMAIL=test@example.org GIT_COMMITTER_NAME=Test GIT_COMMITTER_EMAIL=test@example.org GIT_CONFIG_NOSYSTEM=1 HOME='.escapeshellarg($this->dir);
        exec('cd '.escapeshellarg($this->dir.($cwd !== '' ? '/'.$cwd : '')).' && '.$env.' '.$cmd.' 2>&1', $out, $code);
        $this->assertSame(0, $code, $cmd."\n".implode("\n", $out));

        return trim(implode("\n", $out));
    }

    /** Commit w kopii „pusher” i push na origin. */
    private function commit(string $path, string $content, string $message): void
    {
        File::ensureDirectoryExists(dirname($this->dir.'/pusher/'.$path));
        File::put($this->dir.'/pusher/'.$path, $content);
        $this->sh('git add -A && git commit -q -m '.escapeshellarg($message).' && git push -q origin main', 'pusher');
    }

    /** @return array{0: int, 1: string} kod wyjścia i treść logu */
    private function autopull(): array
    {
        exec('cd '.escapeshellarg($this->dir.'/server').' && HOME='.escapeshellarg($this->dir).' PHP_BIN='.escapeshellarg($this->dir.'/php-stub').' bash cli/autopull.sh 2>&1', $out, $code);
        $log = $this->dir.'/server/storage/logs/autopull.log';

        return [$code, (is_file($log) ? File::get($log) : '').implode("\n", $out)];
    }

    private function sha(string $repo): string
    {
        return $this->sh('git rev-parse HEAD', $repo);
    }

    private function phpCalls(): string
    {
        return is_file($this->dir.'/php-calls.txt') ? File::get($this->dir.'/php-calls.txt') : '';
    }

    public function test_bez_nowych_commitow_nic_nie_robi(): void
    {
        [$code, $log] = $this->autopull();

        $this->assertSame(0, $code, $log);
        $this->assertSame('', trim($log));
        $this->assertSame('', $this->phpCalls());
    }

    public function test_nowy_commit_pobiera_i_odswieza_cache_tras(): void
    {
        $this->commit('routes/web.php', "<?php // nowa trasa\n", 'feat: nowa trasa');

        [$code, $log] = $this->autopull();

        $this->assertSame(0, $code, $log);
        $this->assertSame($this->sha('pusher'), $this->sha('server'));
        $this->assertStringContainsString('pobrano', $log);
        $this->assertStringContainsString('feat: nowa trasa', $log);
        $this->assertStringContainsString('artisan optimize', $this->phpCalls());
        $this->assertStringNotContainsString('wymagają ./deploy.sh', $log);

        // Drugi przebieg bez nowych commitów niczego nie powtarza.
        $this->autopull();
        $this->assertSame(1, substr_count($this->phpCalls(), 'artisan optimize'));
    }

    public function test_migracja_albo_assety_daja_ostrzezenie_o_deploy(): void
    {
        $this->commit('database/migrations/2026_11_23_000000_x.php', "<?php\n", 'feat: migracja');
        $this->commit('resources/js/app.js', "// js\n", 'feat: js');

        [$code, $log] = $this->autopull();

        $this->assertSame(0, $code, $log);
        $this->assertSame($this->sha('pusher'), $this->sha('server'), 'kod i tak jest pobierany');
        $this->assertStringContainsString('wymagają ./deploy.sh', $log);
        $this->assertStringContainsString('database/migrations/2026_11_23_000000_x.php', $log);
        $this->assertStringContainsString('resources/js/app.js', $log);
    }

    public function test_w_trybie_serwisowym_nie_pobiera(): void
    {
        $before = $this->sha('server');
        File::ensureDirectoryExists($this->dir.'/server/storage/framework');
        File::put($this->dir.'/server/storage/framework/down', '{}');
        $this->commit('a.txt', "a\n", 'feat: a');

        [$code] = $this->autopull();

        $this->assertSame(0, $code);
        $this->assertSame($before, $this->sha('server'));
        $this->assertSame('', $this->phpCalls());
    }

    public function test_rozjechane_commity_zostawia_bez_zmian(): void
    {
        File::put($this->dir.'/server/lokalny.txt', "x\n");
        $this->sh('git add lokalny.txt && git commit -q -m lokalny', 'server');
        $before = $this->sha('server');
        $this->commit('b.txt', "b\n", 'feat: b');

        [$code, $log] = $this->autopull();

        $this->assertSame(1, $code, $log);
        $this->assertSame($before, $this->sha('server'));
        $this->assertStringContainsString('rozeszły się', $log);
        $this->assertSame('', $this->phpCalls());
    }

    public function test_inna_galaz_na_serwerze_pomija(): void
    {
        $this->sh('git checkout -q -b kreatywni', 'server');
        $before = $this->sha('server');
        $this->commit('c.txt', "c\n", 'feat: c');

        [$code] = $this->autopull();

        $this->assertSame(0, $code);
        $this->assertSame($before, $this->sha('server'));
    }
}
