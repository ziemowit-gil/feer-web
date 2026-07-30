<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kreacje bannerów — obrazy i widgety HTML/JS wyświetlane w strefach serwisu. */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('image');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('link_url', 1024)->nullable();
            $table->string('link_target', 10)->default('_blank');
            $table->mediumText('html_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
