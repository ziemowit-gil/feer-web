<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('wide_mission_sidebar_style')->default('colored')->after('wide_mission_sidebar');
            $table->string('wide_mission_nav_style')->default('brand_bar')->after('wide_mission_sidebar_style');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['wide_mission_sidebar_style', 'wide_mission_nav_style']);
        });
    }
};
