<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('wide_mission_cta2_label', 80)->nullable()->after('wide_mission_cta_url');
            $table->string('wide_mission_cta2_url', 255)->nullable()->after('wide_mission_cta2_label');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['wide_mission_cta2_label', 'wide_mission_cta2_url']);
        });
    }
};
