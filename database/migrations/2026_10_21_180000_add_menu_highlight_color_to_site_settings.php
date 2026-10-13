<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kolor wyróżnienia pozycji w górnym i bocznym menu (pusty = kolor główny marki). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->string('menu_highlight_color', 7)->nullable());
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('menu_highlight_color'));
    }
};
