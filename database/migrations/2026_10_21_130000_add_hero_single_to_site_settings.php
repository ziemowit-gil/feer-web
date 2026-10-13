<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Strona główna: hero z jednym przekazem (pierwszy slajd, bez karuzeli) zamiast kilku równorzędnych slajdów. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->boolean('hero_single')->default(true));
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('hero_single'));
    }
};
