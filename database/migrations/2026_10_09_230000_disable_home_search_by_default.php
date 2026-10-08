<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wyszukiwarka na stronie głównej wyłączona (można włączyć w Ustawienia → Strona główna). */
    public function up(): void
    {
        if (Schema::hasColumn('site_settings', 'home_search_enabled')) {
            DB::table('site_settings')->update(['home_search_enabled' => false]);
        }
    }

    public function down(): void
    {
    }
};
