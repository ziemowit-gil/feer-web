<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('content_image', 1000)->nullable()->after('content');
            $table->string('content_image_alt', 255)->nullable()->after('content_image');
            $table->string('content_image_width', 30)->nullable()->after('content_image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['content_image', 'content_image_alt', 'content_image_width']);
        });
    }
};
