<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nagłówek kolumny w mega menu: podpozycja z tą flagą otwiera nową kolumnę,
 * a kolejne podpozycje (do następnego nagłówka) trafiają pod nią.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->boolean('is_column_heading')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn('is_column_heading');
        });
    }
};
