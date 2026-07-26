<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tekst wstępu sekcji „Dokumenty i sprawozdania" na stronie typu
     * „O organizacji". Same dokumenty pochodzą z plików do pobrania strony,
     * a przycisk „Zobacz wszystkie" prowadzi do BIP.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('about_documents_intro')->nullable()->after('about_section_order');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('about_documents_intro');
        });
    }
};
