<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('wide_mission_nav_hover_white')->default(false)->after('wide_mission_nav_style');
            $table->boolean('wide_mission_nav_active_white')->default(false)->after('wide_mission_nav_hover_white');
            $table->boolean('wide_mission_nav_icons_white')->default(false)->after('wide_mission_nav_active_white');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['wide_mission_nav_hover_white', 'wide_mission_nav_active_white', 'wide_mission_nav_icons_white']);
        });
    }
};
