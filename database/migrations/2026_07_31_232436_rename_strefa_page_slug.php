<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')
            ->where('slug', 'strefa')
            ->update(['slug' => 'strefa-wspolpracownika-feer']);
    }

    public function down(): void
    {
        DB::table('pages')
            ->where('slug', 'strefa-wspolpracownika-feer')
            ->update(['slug' => 'strefa']);
    }
};
