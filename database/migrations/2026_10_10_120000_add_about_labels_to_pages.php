<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Strona „O organizacji": własne nagłówki i napisy sekcji (puste = domyślne). */
    public function up(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->json('about_labels')->nullable()->after('about_faq_visible'));
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('about_labels'));
    }
};
