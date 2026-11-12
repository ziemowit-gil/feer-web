<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Prawa strona nagłówka FEER edytowana w panelu (Ustawienia → Nagłówek): ikony social i przycisk — jedno pole JSON. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'header_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('header_options')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'header_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('header_options');
            });
        }
    }
};
