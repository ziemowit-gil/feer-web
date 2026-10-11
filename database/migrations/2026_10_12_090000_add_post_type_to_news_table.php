<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Typy wpisów w Aktualnościach: zwykły, „Komunikat” (z datą ważności — po niej trafia do archiwum)
 * i „Test narzędzia” (ocena dostępności, plusy i minusy, wynik audytu w type_data).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('post_type', 20)->default('standard')->after('article_layout');
            $table->json('type_data')->nullable()->after('post_type');
            $table->dateTime('expires_at')->nullable()->index()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['post_type', 'type_data', 'expires_at']);
        });
    }
};
