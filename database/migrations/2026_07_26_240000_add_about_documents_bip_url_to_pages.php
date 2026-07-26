<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Własny odnośnik „Zobacz wszystkie" (BIP / repozytorium dokumentów) dla
     * sekcji „Dokumenty i sprawozdania". Puste = globalny BIP z ustawień.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('about_documents_bip_url')->nullable()->after('about_documents_intro');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('about_documents_bip_url');
        });
    }
};
