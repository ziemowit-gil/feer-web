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
            $table->text('meta_description')->nullable()->after('brand_color');
            $table->string('og_image_path')->nullable()->after('meta_description');
            $table->boolean('allow_indexing')->default(true)->after('og_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['meta_description', 'og_image_path', 'allow_indexing']);
        });
    }
};
