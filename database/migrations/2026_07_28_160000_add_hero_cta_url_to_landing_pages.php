<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // Gdy ustawiony — przycisk „Zapisz się" prowadzi na ten adres
            // (zewnętrzny system zapisu) zamiast do wbudowanego formularza.
            $table->string('hero_cta_url')->nullable()->after('hero_cta_label');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', fn (Blueprint $t) => $t->dropColumn('hero_cta_url'));
    }
};
