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
        Schema::table('gallery_images', fn (Blueprint $table) => $table->dropColumn('image_path'));
        Schema::table('hero_slides', fn (Blueprint $table) => $table->dropColumn('image_path'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('image_path'));
        Schema::table('news', fn (Blueprint $table) => $table->dropColumn(['image_path', 'image_width', 'image_height']));
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['logo_path', 'og_image_path']));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gallery_images', fn (Blueprint $table) => $table->string('image_path')->nullable());
        Schema::table('hero_slides', fn (Blueprint $table) => $table->string('image_path')->nullable());
        Schema::table('projects', fn (Blueprint $table) => $table->string('image_path')->nullable());
        Schema::table('news', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('og_image_path')->nullable();
        });
    }
};
