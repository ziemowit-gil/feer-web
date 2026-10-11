<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Zdjęcie działania jako hero: szeroki baner nad nagłówkiem zamiast zdjęcia obok tytułu. */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('image_hero')->default(false)->after('image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('image_hero');
        });
    }
};
