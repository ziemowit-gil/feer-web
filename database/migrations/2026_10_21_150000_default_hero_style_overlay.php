<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Domyślny układ nagłówka przy zdjęciu-hero: klasyczny (karta nachodząca na zdjęcie). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->string('hero_style', 10)->default('overlay')->change());
        DB::table('projects')->update(['hero_style' => 'overlay']);
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->string('hero_style', 10)->default('band')->change());
    }
};
