<?php

namespace Tests\Feature\System;

use App\Support\BlogDatabaseMover;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

/** `artisan blog:przenies-baze` — kopia + weryfikacja na plikach tymczasowych. */
class BlogMoveDatabaseTest extends TestCase
{
    private string $dir;

    private string $source;

    private string $target;

    private string $backups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/blog-move-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->source = $this->dir.'/database/blog.sqlite';
        $this->target = $this->dir.'/storage/blog/blog.sqlite';
        $this->backups = $this->dir.'/storage/blog/backups';
        mkdir(dirname($this->source));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function makeSource(bool $wal = false): void
    {
        $pdo = new PDO('sqlite:'.$this->source);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($wal) {
            $pdo->exec('PRAGMA journal_mode = WAL');
        }
        $pdo->exec('CREATE TABLE blog_articles (id INTEGER PRIMARY KEY, title TEXT)');
        $pdo->exec('CREATE TABLE blog_comments (id INTEGER PRIMARY KEY, body TEXT)');
        $pdo->exec('CREATE TABLE "dziwna""nazwa" (id INTEGER PRIMARY KEY)');
        for ($i = 1; $i <= 25; $i++) {
            $pdo->exec("INSERT INTO blog_articles (title) VALUES ('Artykuł {$i}')");
        }
        $pdo->exec("INSERT INTO blog_comments (body) VALUES ('a'), ('b'), ('c')");
    }

    private function args(array $extra = []): array
    {
        return array_merge([
            '--zrodlo' => $this->source,
            '--cel' => $this->target,
            '--kopie' => $this->backups,
        ], $extra);
    }

    public function test_copies_database_with_backup_and_matching_row_counts(): void
    {
        $this->makeSource();
        $hashBefore = md5_file($this->source);

        $this->artisan('blog:przenies-baze', $this->args(['--force' => true]))->assertSuccessful();

        $this->assertFileExists($this->target);
        $this->assertSame($hashBefore, md5_file($this->source), 'Źródło nie może się zmienić.');
        $this->assertCount(1, glob($this->backups.'/blog-*.sqlite'));
        $this->assertSame([], glob(dirname($this->target).'/*.tmp-*'));

        $mover = new BlogDatabaseMover;
        $counts = $mover->rowCounts($mover->open($this->target));
        $this->assertSame(['blog_articles' => 25, 'blog_comments' => 3, 'dziwna"nazwa' => 0], $counts);
    }

    public function test_copies_wal_database_including_uncheckpointed_rows(): void
    {
        $this->makeSource(wal: true);

        $result = (new BlogDatabaseMover)->move($this->source, $this->target, $this->backups);

        $this->assertSame(25, $result['tables']['blog_articles']);
        $mover = new BlogDatabaseMover;
        $this->assertSame(25, $mover->rowCounts($mover->open($this->target))['blog_articles']);
    }

    public function test_existing_target_is_left_untouched(): void
    {
        $this->makeSource();
        mkdir(dirname($this->target), 0777, true);
        file_put_contents($this->target, 'istniejąca');

        $this->artisan('blog:przenies-baze', $this->args(['--force' => true]))->assertSuccessful();

        $this->assertSame('istniejąca', file_get_contents($this->target));
    }

    public function test_missing_source_creates_nothing(): void
    {
        $this->artisan('blog:przenies-baze', $this->args(['--force' => true]))->assertSuccessful();

        $this->assertFileDoesNotExist($this->target);
    }

    public function test_corrupted_source_fails_without_creating_target(): void
    {
        file_put_contents($this->source, str_repeat('to nie jest baza SQLite ', 200));

        $this->artisan('blog:przenies-baze', $this->args(['--force' => true]))->assertFailed();

        $this->assertFileDoesNotExist($this->target);
    }

    public function test_refuses_outside_maintenance_mode_without_confirmation(): void
    {
        $this->makeSource();

        $this->artisan('blog:przenies-baze', $this->args())
            ->expectsConfirmation('Kontynuować mimo to?', 'no')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->target);
    }
}
