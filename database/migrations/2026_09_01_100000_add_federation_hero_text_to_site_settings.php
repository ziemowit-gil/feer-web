<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('federation_hero_heading')->nullable()->after('federation_join_benefits');
            $table->text('federation_hero_intro')->nullable()->after('federation_hero_heading');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['federation_hero_heading', 'federation_hero_intro']);
        });
    }
};
