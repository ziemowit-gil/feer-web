<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalne elementy portalu kategorii (komunikat, „Na skróty”, „Pozostańmy w kontakcie”, nagłówki widżetów)
 * w jednej kolumnie JSON — klucze opisuje Category::portalOption().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->json('portal_options')->nullable()->after('portal_widgets');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('portal_options');
        });
    }
};
