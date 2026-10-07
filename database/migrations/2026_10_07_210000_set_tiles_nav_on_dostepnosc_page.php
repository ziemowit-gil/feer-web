<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Podstrona „Dostępność": domyślnie kafelkowe menu podstron (opcja „Nawigacja kafelkowa"), włączone. */
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasColumn('pages', 'side_nav_style')) {
            return;
        }

        DB::table('pages')
            ->where('slug', 'dostepnosc')
            ->whereNull('parent_id')
            ->where('side_nav_style', 'sidebar')
            ->update(['side_nav_style' => 'tiles', 'show_side_nav' => true]);
    }

    public function down(): void
    {
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'side_nav_style')) {
            DB::table('pages')->where('slug', 'dostepnosc')->whereNull('parent_id')->where('side_nav_style', 'tiles')->update(['side_nav_style' => 'sidebar']);
        }
    }
};
