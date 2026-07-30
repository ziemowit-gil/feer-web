<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Znacznik zarchiwizowania dla wydarzeń i ogłoszeń wolontariatu. Puste = pozycja
 * aktywna; ustawione = schowana z domyślnych list panelu (auto-archiwizacja
 * przeterminowanych albo ręczne schowanie). Publiczne listy i tak filtrują po
 * dacie, więc archiwizacja dotyczy porządku w panelu, nie widoczności na stronie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_featured');
        });

        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
