<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Usuwa testowe pozycje („test") z menu BIP, które były widoczne publicznie. */
    public function up(): void
    {
        DB::table('nav_items')->where('location', 'bip')->whereRaw('LOWER(TRIM(label)) = ?', ['test'])->delete();
    }

    public function down(): void
    {
    }
};
