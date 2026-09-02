<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('federation_colorful_nav')->default(true)->after('federation_hero_tiles');
            $table->json('federation_colorful_nav_items')->nullable()->after('federation_colorful_nav');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['federation_colorful_nav', 'federation_colorful_nav_items']);
        });
    }
};
