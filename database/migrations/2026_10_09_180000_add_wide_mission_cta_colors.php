<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Własny kolor dla dwóch dodatkowych przycisków w nagłówku (puste = kolor marki). */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('wide_mission_cta_color', 7)->nullable();
            $table->string('wide_mission_cta2_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['wide_mission_cta_color', 'wide_mission_cta2_color']));
    }
};
