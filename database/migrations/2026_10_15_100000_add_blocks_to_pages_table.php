<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Struktura bloków z edytora blokowego (JSON). Źródło prawdy dla edytora;
 * kolumna `content` dostaje wyrenderowany z bloków HTML, więc front, wyszukiwarka
 * i historia wersji działają bez zmian.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'blocks')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->json('blocks')->nullable()->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'blocks')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('blocks');
            });
        }
    }
};
