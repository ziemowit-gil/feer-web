<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Wyróżnienie pojedynczej pozycji górnego menu (kolor wyróżnienia z Ustawienia → Kolory). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_items', fn (Blueprint $table) => $table->boolean('highlight')->default(false));
    }

    public function down(): void
    {
        Schema::table('nav_items', fn (Blueprint $table) => $table->dropColumn('highlight'));
    }
};
