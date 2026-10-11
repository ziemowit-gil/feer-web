<?php

namespace Tests\Feature\System;

use App\Models\User;
use App\Support\DeployHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Kontrole wdrożenia: manifest Vite, oczekujące migracje, harmonogram, komenda i panel. */
class DeployHealthTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/deploy-health-'.bin2hex(random_bytes(4));
        mkdir($this->dir);

        config(['database.connections.blog.database' => ':memory:']);
        DB::purge('blog');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function health(): DeployHealth
    {
        return $this->app->make(DeployHealth::class);
    }

    private function viteConfig(): string
    {
        $path = $this->dir.'/vite.config.js';
        file_put_contents($path, <<<'JS'
            export default defineConfig({
                plugins: [
                    laravel({
                        input: [
                            'resources/css/app.css',
                            "resources/js/app.js",
                            'resources/css/block-editor.css',
                        ],
                        refresh: true,
                    }),
                ],
            });
            JS);

        return $path;
    }

    private function manifest(array $keys): string
    {
        $path = $this->dir.'/manifest.json';
        file_put_contents($path, json_encode(array_fill_keys($keys, ['file' => 'assets/x.js'])));

        return $path;
    }

    public function test_parses_vite_inputs(): void
    {
        $this->assertSame(
            ['resources/css/app.css', 'resources/js/app.js', 'resources/css/block-editor.css'],
            $this->health()->viteInputs($this->viteConfig()),
        );
    }

    public function test_parses_the_real_vite_config(): void
    {
        $inputs = $this->health()->viteInputs(base_path('vite.config.js'));

        $this->assertContains('resources/css/app.css', $inputs);
        $this->assertContains('resources/js/app.js', $inputs);
    }

    public function test_manifest_with_all_inputs_is_ok(): void
    {
        $row = $this->health()->checkManifest(
            $this->manifest(['resources/css/app.css', 'resources/js/app.js', 'resources/css/block-editor.css', 'extra.js']),
            $this->viteConfig(),
        );

        $this->assertSame('ok', $row['status']);
    }

    public function test_manifest_missing_an_input_is_an_error(): void
    {
        $row = $this->health()->checkManifest(
            $this->manifest(['resources/css/app.css', 'resources/js/app.js']),
            $this->viteConfig(),
        );

        $this->assertSame('error', $row['status']);
        $this->assertStringContainsString('resources/css/block-editor.css', $row['detail']);
    }

    public function test_missing_or_broken_manifest_is_an_error(): void
    {
        $this->assertSame('error', $this->health()->checkManifest($this->dir.'/brak.json', $this->viteConfig())['status']);

        file_put_contents($this->dir.'/zly.json', '{nie json');
        $this->assertSame('error', $this->health()->checkManifest($this->dir.'/zly.json', $this->viteConfig())['status']);
    }

    public function test_detects_pending_migrations_on_the_main_database(): void
    {
        $this->assertSame([], $this->health()->pendingMigrations(null, [database_path('migrations')]));

        $extra = $this->dir.'/migrations';
        mkdir($extra);
        file_put_contents($extra.'/2099_01_01_000000_create_test_table.php', '<?php return new class extends Illuminate\Database\Migrations\Migration {};');

        $this->assertSame(
            ['2099_01_01_000000_create_test_table'],
            $this->health()->pendingMigrations(null, [database_path('migrations'), $extra]),
        );
        $row = $this->health()->checkMigrations(null, [database_path('migrations'), $extra], 'Migracje');
        $this->assertSame('error', $row['status']);
        $this->assertStringContainsString('1 oczekujących', $row['detail']);
    }

    public function test_detects_pending_migrations_on_the_blog_database(): void
    {
        $path = [database_path('migrations/blog')];

        $pending = $this->health()->pendingMigrations('blog', $path);
        $this->assertNotEmpty($pending, 'Niezmigrowana baza bloga — wszystkie migracje oczekują.');
        $this->assertSame('error', $this->health()->checkMigrations('blog', $path, 'Blog')['status']);

        $this->artisan('migrate', ['--database' => 'blog', '--path' => 'database/migrations/blog'])->assertSuccessful();

        $this->assertSame([], $this->health()->pendingMigrations('blog', $path));
        $this->assertSame('ok', $this->health()->checkMigrations('blog', $path, 'Blog')['status']);
        // Sprawdzenie innego połączenia nie zmienia domyślnego.
        $this->assertSame(config('database.default'), DB::getDefaultConnection());
    }

    public function test_blog_database_file_checks(): void
    {
        $file = $this->dir.'/blog.sqlite';
        $this->assertSame('error', $this->health()->checkBlogDatabase($file)['status']);

        touch($file);
        $this->assertSame('ok', $this->health()->checkBlogDatabase($file)['status']);
    }

    public function test_scheduler_heartbeat(): void
    {
        $this->assertSame('warning', $this->health()->checkScheduler()['status']);

        DeployHealth::recordHeartbeat();
        $this->assertSame('ok', $this->health()->checkScheduler()['status']);

        Cache::put(DeployHealth::HEARTBEAT_KEY, now()->subMinutes(11)->getTimestamp());
        $this->assertSame('warning', $this->health()->checkScheduler()['status']);
    }

    public function test_heartbeat_is_scheduled_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'system:heartbeat');

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_command_exit_codes(): void
    {
        $this->artisan('system:sprawdz', ['--tylko' => 'nieznana'])->assertExitCode(2);

        // Niezmigrowana baza bloga → problem → kod 1.
        $this->artisan('system:sprawdz', ['--tylko' => 'migracje'])->assertExitCode(1);

        $this->artisan('migrate', ['--database' => 'blog', '--path' => 'database/migrations/blog'])->assertSuccessful();
        $this->artisan('system:sprawdz', ['--tylko' => 'migracje'])->assertExitCode(0);

        // Ostrzeżenie (brak heartbeatu) nie psuje wyniku, chyba że --scisle.
        $this->artisan('system:sprawdz', ['--tylko' => 'harmonogram'])->assertExitCode(0);
        $this->artisan('system:sprawdz', ['--tylko' => 'harmonogram', '--scisle' => true])->assertExitCode(1);
    }

    public function test_admin_health_page_shows_deploy_section(): void
    {
        Http::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.health.index'))
            ->assertOk()
            ->assertSee('Wdrożenie')
            ->assertSee('Migracje — baza bloga')
            ->assertSee('Harmonogram (schedule:run)')
            ->assertSee('Assety (manifest Vite)');
    }
}
