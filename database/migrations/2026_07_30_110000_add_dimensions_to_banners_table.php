<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Opcjonalne wymiary kreacji (px) — sterują rozmiarem wyświetlania i stabilnością layoutu. */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->unsignedSmallInteger('width')->nullable()->after('image_alt');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['width', 'height']);
        });
    }
};
