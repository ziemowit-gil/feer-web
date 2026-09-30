<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konfigurowalna karta boczna mega menu: opcjonalny tytuł i lista własnych
     * linków / przycisków (np. do działań na starej stronie), które zastępują
     * domyślne przyciski.
     */
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->string('mega_side_title', 80)->nullable()->after('mega_extra_title');
            $table->json('mega_side_links')->nullable()->after('mega_side_title');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn(['mega_side_title', 'mega_side_links']);
        });
    }
};
