<?php

namespace Tests\Feature;

use App\Models\BannerZone;
use App\Models\Page;
use App\Models\User;
use App\Support\Database\SqliteToMysqlConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Konwerter SQLite → MySQL (System → Baza MySQL, db:konwertuj-mysql). MySQL nie jest dostępny w testach,
 * więc logikę kopiowania sprawdzamy na docelowym pliku SQLite (ten sam kod, bez poleceń specyficznych dla MySQL),
 * a panel — na walidacji, błędzie połączenia i zapisie .env w pliku tymczasowym.
 */
class SqliteToMysqlConverterTest extends TestCase
{
    use RefreshDatabase;

    private string $targetFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->targetFile = sys_get_temp_dir().'/konwersja-'.uniqid().'.sqlite';
        touch($this->targetFile);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        DB::purge(SqliteToMysqlConverter::TARGET);
        @unlink($this->targetFile);
        parent::tearDown();
    }

    private function converter(): SqliteToMysqlConverter
    {
        return new SqliteToMysqlConverter(null, ['driver' => 'sqlite', 'database' => $this->targetFile, 'prefix' => '', 'foreign_key_constraints' => true]);
    }

    public function test_kopiuje_wszystkie_tabele_i_zgadza_sie_liczba_wierszy(): void
    {
        Page::create(['title' => 'O nas', 'slug' => 'o-nas', 'content' => '<p>Treść z polskimi znakami: zażółć gęślą jaźń</p>', 'is_published' => true]);
        User::factory()->count(3)->create();
        BannerZone::create(['slug' => 'na-skroty', 'label' => 'Na skróty']);
        $zones = BannerZone::count();

        $report = $this->converter()->convert();

        $this->assertTrue($report['ok'], json_encode(array_filter($report['tables'], fn ($r) => ! $r['ok'])));
        $dst = DB::connection(SqliteToMysqlConverter::TARGET);
        $this->assertSame('<p>Treść z polskimi znakami: zażółć gęślą jaźń</p>', $dst->table('pages')->where('slug', 'o-nas')->value('content'));
        $this->assertSame(3, $dst->table('users')->count());
        // Wiersze z migracji (domyślne strefy) nie dublują się — jest dokładnie to, co w źródle.
        $this->assertSame($zones, $dst->table('banner_zones')->count());
        $this->assertSame(DB::table('migrations')->count(), $dst->table('migrations')->count());
        $this->assertContains('pages', array_column($report['tables'], 'table'));
    }

    public function test_niepusta_baza_docelowa_wymaga_zastapienia(): void
    {
        $this->converter()->convert();

        try {
            $this->converter()->convert();
            $this->fail('Powinien być błąd o niepustej bazie.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Zastąp zawartość bazy docelowej', $e->getMessage());
        }

        Page::create(['title' => 'Nowa', 'slug' => 'nowa', 'content' => '', 'is_published' => true]);
        $report = $this->converter()->convert(true);
        $this->assertTrue($report['ok']);
        $this->assertSame(1, DB::connection(SqliteToMysqlConverter::TARGET)->table('pages')->where('slug', 'nowa')->count());
    }

    public function test_przelaczenie_env_z_kopia_i_powrot(): void
    {
        $env = sys_get_temp_dir().'/env-'.uniqid();
        file_put_contents($env, "APP_NAME=FEER\nDB_CONNECTION=sqlite\n");

        $backup = SqliteToMysqlConverter::switchEnv(['host' => 'localhost', 'port' => 3306, 'database' => 'feer', 'username' => 'feer_user', 'password' => 'ta$ne h#slo'], $env);

        $content = file_get_contents($env);
        $this->assertStringContainsString("DB_CONNECTION=mysql\n", $content);
        $this->assertStringContainsString("DB_DATABASE=feer\n", $content);
        $this->assertStringContainsString('DB_PASSWORD="ta\\$ne h#slo"', $content);
        $this->assertStringContainsString('APP_NAME=FEER', $content);
        $this->assertSame("APP_NAME=FEER\nDB_CONNECTION=sqlite\n", file_get_contents($backup));
        $this->assertSame($backup, SqliteToMysqlConverter::latestEnvBackup($env));

        SqliteToMysqlConverter::restoreEnv($env);
        $this->assertSame("APP_NAME=FEER\nDB_CONNECTION=sqlite\n", file_get_contents($env));

        array_map('unlink', glob($env.'*'));
    }

    public function test_panel_tylko_dla_admina_z_walidacja_i_bledem_polaczenia(): void
    {
        $editor = User::factory()->create(['role' => User::ROLE_CONTENT_EDITOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($editor)->get(route('admin.baza-mysql.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.baza-mysql.index'))->assertOk()
            ->assertSee('Baza MySQL')->assertSee('Obecnie serwis działa na: <strong>SQLite</strong>', false)
            ->assertSee('<label for="db-password"', false)->assertSee('Konwertuj do MySQL');

        $this->actingAs($admin)->from(route('admin.baza-mysql.index'))->post(route('admin.baza-mysql.check'), ['host' => '', 'port' => 'x', 'database' => 'zła baza!', 'username' => ''])
            ->assertSessionHasErrors(['host', 'port', 'database', 'username']);

        // Port 1 na localhost — połączenie odrzucone, czytelny komunikat bez hasła.
        $this->actingAs($admin)->from(route('admin.baza-mysql.index'))->post(route('admin.baza-mysql.check'), ['host' => '127.0.0.1', 'port' => 1, 'database' => 'feer', 'username' => 'u', 'password' => 'sekret'])
            ->assertSessionHasErrors('connection');
        $this->assertStringNotContainsString('sekret', (string) session('errors')?->first('connection'));

        $this->actingAs($admin)->post(route('admin.baza-mysql.convert'))->assertSessionHasErrors('connection');
        $this->actingAs($admin)->post(route('admin.baza-mysql.switch'), ['confirm' => '1'])->assertSessionHasErrors('switch');
    }

    public function test_polecenie_konsoli_zglasza_blad_polaczenia(): void
    {
        $this->artisan('db:konwertuj-mysql', ['--host' => '127.0.0.1', '--port' => 1, '--database' => 'feer', '--username' => 'u', '--password' => 'x', '--sprawdz' => true])
            ->expectsOutputToContain('Nie udało się połączyć z bazą docelową')
            ->assertFailed();
    }
}
