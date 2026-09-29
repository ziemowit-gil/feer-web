<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wspólny słownik JSON dla nowych typów treści (oferta/usługa, poradnik,
     * słownik pojęć, studium przypadku) — zamiast osobnej kolumny na każde pole.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->json('type_data')->nullable()->after('cooperation_data');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('type_data');
        });
    }
};
