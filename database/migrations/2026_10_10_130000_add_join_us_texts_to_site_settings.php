<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Strona „Dołącz do nas": własne nagłówki, opisy kafli i napisy (puste = domyślne). */
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->json('join_us_texts')->nullable());
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('join_us_texts'));
    }
};
