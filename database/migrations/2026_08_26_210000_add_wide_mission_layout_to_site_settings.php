<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Wariant rozmieszczenia elementów w nagłówku „Szeroka belka".
            $table->string('wide_mission_layout', 20)->default('right')->after('wide_mission_social_3');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('wide_mission_layout');
        });
    }
};
