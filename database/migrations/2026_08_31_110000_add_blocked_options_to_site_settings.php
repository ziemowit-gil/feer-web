<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ograniczenia wyboru dla całej instalacji, ustalane przez wdrażającego w
 * kreatorze — np. które układy nagłówka, typy podstron czy warianty strony
 * kontaktowej mają być zablokowane dla administratorów tej instalacji.
 *
 * Struktura JSON: {"header_layouts": ["wide_mission"], "page_types": [...], "contact_layouts": [...]}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('blocked_options')->nullable()->after('header_layout');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('blocked_options');
        });
    }
};
