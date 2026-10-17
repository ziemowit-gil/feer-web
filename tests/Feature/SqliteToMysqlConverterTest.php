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

    public function test_konwersja_z_panelu_przy_zalogowanym_adminie(): void
    {
        // Migracje z danymi zapisują strony, a obserwator wersji wpisuje user_id zalogowanej osoby —
        // w nowej bazie tabela users jest jeszcze pusta, więc klucz obcy blokowałby zapis.
        // Logowanie przez sesję jak w panelu (actingAs nie zapisuje sesji, więc nie odtwarzało błędu).
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->startSession();
        auth()->login($admin);
        Page::create(['title' => 'Strona', 'slug' => 'strona', 'content' => '<p>x</p>', 'is_published' => true]);
        $usersInMigrations = [];
        \App\Models\ContentRevision::creating(function ($r) use (&$usersInMigrations) {
            if ($r->getConnectionName() === SqliteToMysqlConverter::TARGET || DB::getDefaultConnection() === SqliteToMysqlConverter::TARGET) {
                $usersInMigrations[] = $r->user_id;
            }
        });

        $report = $this->converter()->convert(true);

        $this->assertNotEmpty($usersInMigrations, 'Migracje z danymi zapisują wersje stron.');
        $this->assertSame([null], array_values(array_unique($usersInMigrations)), 'Wersje z migracji bez user_id zalogowanej osoby.');

        $this->assertTrue($report['ok'], json_encode(array_filter($report['tables'], fn ($r) => ! $r['ok'])));
        $this->assertSame($admin->id, auth()->id(), 'Po konwersji osoba nadal jest zalogowana.');
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame(DB::table('content_revisions')->count(), DB::connection(SqliteToMysqlConverter::TARGET)->table('content_revisions')->count());
    }

    public function test_wiersz_dopisany_w_trakcie_kopiowania_trafia_do_kopii_i_raport_jest_zgodny(): void
    {
        activity('cms')->log('przed konwersją');
        $added = false;
        $copying = false;
        $source = config('database.default');
        // Kopiowanie zaczyna się po wyczyszczeniu tabeli docelowej (migracje też zapisują dziennik) —
        // przy pierwszej paczce serwis dopisuje wpis w SQLite.
        DB::listen(function ($q) use (&$added, &$copying, $source) {
            if ($q->connectionName !== SqliteToMysqlConverter::TARGET) {
                return;
            }
            if (str_contains($q->sql, 'delete from "activity_log"')) {
                $copying = true;
            } elseif ($copying && ! $added && str_contains($q->sql, 'insert into "activity_log"')) {
                $added = true;
                DB::connection($source)->table('activity_log')->insert(['log_name' => 'cms', 'description' => 'w trakcie konwersji', 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        $report = $this->converter()->convert();

        $row = collect($report['tables'])->firstWhere('table', 'activity_log');
        $this->assertTrue($added);
        $this->assertTrue($row['ok'], json_encode($row));
        $this->assertSame($row['source'], $row['target']);
        $this->assertStringContainsString('W trakcie kopiowania w SQLite przybyło wierszy: 1', (string) $row['note']);
        $this->assertSame(1, DB::connection(SqliteToMysqlConverter::TARGET)->table('activity_log')->where('description', 'w trakcie konwersji')->count());
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

    /** SQL MySQL bez serwera: połączenie bez PDO wystarczy do skompilowania schematu. */
    private function mysqlSql(callable $define, string $table, bool $create = true): string
    {
        $conn = new \Illuminate\Database\MySqlConnection(fn () => null, 'x', '', ['driver' => 'mysql', 'name' => 'x']);
        $grammar = new \App\Support\Database\WideTableMySqlGrammar($conn);
        $grammar->wideTables = ['site_settings'];
        $conn->setSchemaGrammar($grammar);
        $bp = new \Illuminate\Database\Schema\Blueprint($conn, $table);
        if ($create) {
            $bp->create();
        }
        $define($bp);

        return implode(";\n", $bp->toSql());
    }

    public function test_szeroka_tabela_ma_kolumny_text_zamiast_varchar_z_domyslna_wartoscia_jako_wyrazenie(): void
    {
        $sql = $this->mysqlSql(function ($t) {
            $t->id();
            $t->string('site_name')->default("Fundacja 'FEER'");
            $t->string('slug')->nullable()->unique();
            $t->string('kod', 20)->nullable();
        }, 'site_settings');

        $this->assertStringContainsString("`site_name` text not null default ('Fundacja ''FEER''')", $sql);
        $this->assertStringContainsString('`slug` varchar(255) null', $sql); // z indeksem — zostaje VARCHAR
        $this->assertStringContainsString('`kod` text null', $sql);

        $other = $this->mysqlSql(fn ($t) => $t->string('title')->default('x'), 'pages');
        $this->assertStringContainsString("`title` varchar(255) not null default 'x'", $other);

        $change = $this->mysqlSql(fn ($t) => $t->string('tagline')->nullable()->default(null)->change(), 'site_settings', false);
        $this->assertStringContainsString('modify `tagline` text null', $change);
    }

    public function test_konwersja_pomija_after_na_nieistniejaca_kolumne(): void
    {
        $sql = $this->mysqlSql(fn ($t) => $t->string('news_layout', 20)->default('grid')->after('news_default_image'), 'pages', false);

        $this->assertStringContainsString("alter table `pages` add `news_layout` varchar(20) not null default 'grid'", $sql);
        $this->assertStringNotContainsString('after', $sql);
        $this->assertStringNotContainsString("->default('grid')->after('news_default_image')", file_get_contents(database_path('migrations/2026_07_30_100000_add_layout_options_to_site_settings.php')));
    }

    public function test_wykrywa_szerokie_tabele_w_zrodle(): void
    {
        $this->assertSame(['site_settings'], $this->converter()->wideTables());
    }

    public function test_polecenie_konsoli_zglasza_blad_polaczenia(): void
    {
        $this->artisan('db:konwertuj-mysql', ['--host' => '127.0.0.1', '--port' => 1, '--database' => 'feer', '--username' => 'u', '--password' => 'x', '--sprawdz' => true])
            ->expectsOutputToContain('Nie udało się połączyć z bazą docelową')
            ->assertFailed();
    }
}
