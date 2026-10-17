<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SQLite w produkcji: transakcje IMMEDIATE i busy_timeout, żeby równoległe zadania (harmonogram,
 * worker kolejki, WWW) czekały na blokadę zamiast padać z „database is locked”.
 */
class SqliteLockingConfigTest extends TestCase
{
    public function test_obie_bazy_sqlite_maja_immediate_i_busy_timeout(): void
    {
        $config = require base_path('config/database.php');

        foreach (['sqlite', 'blog'] as $name) {
            $this->assertSame('IMMEDIATE', $config['connections'][$name]['transaction_mode'], $name);
            $this->assertSame(10000, $config['connections'][$name]['busy_timeout'], $name);
        }
    }

    public function test_transakcja_dziala_z_trybem_immediate(): void
    {
        config(['database.connections.lock_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'busy_timeout' => 10000, 'transaction_mode' => 'IMMEDIATE']]);
        $db = DB::connection('lock_test');
        $db->statement('create table t (v integer)');

        $db->transaction(fn () => $db->table('t')->insert(['v' => 1]));

        $this->assertSame(1, $db->table('t')->count());
        $this->assertSame(10000, (int) $db->selectOne('pragma busy_timeout')->timeout);
    }
}
