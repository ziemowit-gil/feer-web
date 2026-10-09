<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Zmiana nazwy sekcji „Projekty" → „Działania": adresy i etykiety w menu oraz w szybkich akcjach. */
    public function up(): void
    {
        foreach (['nav_items' => ['url', 'label'], 'quick_actions' => ['url', 'label']] as $table => [$urlCol, $labelCol]) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->where($urlCol, '/projekty')->update([$urlCol => '/dzialania']);
            DB::table($table)->where($urlCol, 'like', '/projekty/%')->update([$urlCol => DB::raw("REPLACE($urlCol, '/projekty/', '/dzialania/')")]);
            DB::table($table)->whereIn($labelCol, ['Projekty', 'Nasze projekty', 'Wszystkie projekty'])->update([$labelCol => DB::raw("REPLACE(REPLACE(REPLACE($labelCol, 'Wszystkie projekty', 'Wszystkie działania'), 'Nasze projekty', 'Nasze działania'), 'Projekty', 'Działania')")]);
        }
    }

    public function down(): void
    {
    }
};
