<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standardowy kolor marki FEER to #1e6dff; zmieniamy tylko wartość domyślną z dawnego koloru.
        DB::table('site_settings')->where('brand_color', '#c31432')->update(['brand_color' => '#1e6dff']);
        // Drukowanie wyłączone (można włączyć w ustawieniach).
        if (Schema::hasColumn('site_settings', 'show_print_button')) {
            DB::table('site_settings')->update(['show_print_button' => false]);
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->where('brand_color', '#1e6dff')->update(['brand_color' => '#c31432']);
    }
};
