<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stopka edytowana przez administratora (Ustawienia → Ogólne → Stopka): tekst, stałe linki i ikony — jedno pole JSON. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'footer_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('footer_options')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'footer_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('footer_options');
            });
        }
    }
};
