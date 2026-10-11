<?php

namespace Tests\Feature\System;

use App\Support\BlogDatabasePath;
use PHPUnit\Framework\TestCase;

/** Kolejność wyboru pliku bazy bloga w config/database.php. */
class BlogDatabasePathTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/blog-path-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir.'/new.sqlite');
        @rmdir($this->dir);
        parent::tearDown();
    }

    public function test_env_value_wins(): void
    {
        touch($this->dir.'/new.sqlite');

        $this->assertSame('/custom/blog.sqlite', BlogDatabasePath::resolve('/custom/blog.sqlite', $this->dir.'/new.sqlite', '/legacy.sqlite'));
    }

    public function test_new_location_is_used_once_the_file_exists(): void
    {
        touch($this->dir.'/new.sqlite');

        $this->assertSame($this->dir.'/new.sqlite', BlogDatabasePath::resolve(null, $this->dir.'/new.sqlite', '/legacy.sqlite'));
    }

    public function test_empty_env_value_is_treated_as_unset(): void
    {
        touch($this->dir.'/new.sqlite');

        $this->assertSame($this->dir.'/new.sqlite', BlogDatabasePath::resolve('  ', $this->dir.'/new.sqlite', '/legacy.sqlite'));
    }

    public function test_falls_back_to_legacy_location_until_the_copy_exists(): void
    {
        $this->assertSame('/legacy.sqlite', BlogDatabasePath::resolve(null, $this->dir.'/new.sqlite', '/legacy.sqlite'));
        $this->assertSame('/legacy.sqlite', BlogDatabasePath::resolve('', $this->dir.'/new.sqlite', '/legacy.sqlite'));
    }
}
