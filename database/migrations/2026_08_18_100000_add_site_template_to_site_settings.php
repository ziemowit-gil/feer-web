<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('site_template')->default('default')->after('header_layout');
            $table->string('municipality_shortcuts_slug')->nullable()->after('site_template');
            $table->string('municipality_carousel_title')->nullable()->after('municipality_shortcuts_slug');
            $table->decimal('municipality_weather_lat', 9, 6)->nullable()->after('municipality_carousel_title');
            $table->decimal('municipality_weather_lon', 9, 6)->nullable()->after('municipality_weather_lat');
            $table->boolean('municipality_show_google_translate')->default(false)->after('municipality_weather_lon');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_template',
                'municipality_shortcuts_slug',
                'municipality_carousel_title',
                'municipality_weather_lat',
                'municipality_weather_lon',
                'municipality_show_google_translate',
            ]);
        });
    }
};
