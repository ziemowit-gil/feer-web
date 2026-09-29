<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sposób prezentacji podstron działu: boczne drzewo (`sidebar`, dotychczasowe)
     * albo poziome zakładki nad treścią (`tabs`).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('side_nav_style', 20)->default('sidebar')->after('show_side_nav');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('side_nav_style');
        });
    }
};
