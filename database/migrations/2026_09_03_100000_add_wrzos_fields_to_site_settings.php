<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalna treść strony głównej szablonu "wrzos" (blok "Kim jesteśmy?"
 * i karty wartości) — jedyny szablon, w którym te pola mają zastosowanie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('wrzos_intro_heading')->nullable()->after('federation_join_benefits');
            $table->text('wrzos_intro_text')->nullable()->after('wrzos_intro_heading');
            $table->json('wrzos_values')->nullable()->after('wrzos_intro_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['wrzos_intro_heading', 'wrzos_intro_text', 'wrzos_values']);
        });
    }
};
