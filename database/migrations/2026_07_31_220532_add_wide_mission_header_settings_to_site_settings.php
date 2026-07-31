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
            $table->string('wide_mission_social_1', 20)->nullable()->after('header_layout');
            $table->string('wide_mission_social_2', 20)->nullable()->after('wide_mission_social_1');
            $table->string('wide_mission_cta_label', 80)->nullable()->after('wide_mission_social_2');
            $table->string('wide_mission_cta_url', 255)->nullable()->after('wide_mission_cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'wide_mission_social_1',
                'wide_mission_social_2',
                'wide_mission_cta_label',
                'wide_mission_cta_url',
            ]);
        });
    }
};
