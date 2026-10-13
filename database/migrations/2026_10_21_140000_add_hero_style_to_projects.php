<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Układ nagłówka działania przy zdjęciu-hero: band (niski pasek, tytuł pod nim) albo overlay (karta nachodząca na duże zdjęcie). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->string('hero_style', 10)->default('band'));
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('hero_style'));
    }
};
