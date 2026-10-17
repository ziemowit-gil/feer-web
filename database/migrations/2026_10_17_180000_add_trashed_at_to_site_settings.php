<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kosz witryn sieci: usunięta sub-witryna dostaje trashed_at i przez 30 dni można ją przywrócić
 * (potem witryny:oproznij-kosz usuwa ją trwale). Osobna kolumna zamiast SoftDeletes, żeby globalny
 * zakres nie zmieniał SiteSetting::current() i relacji. Bez after() — produkcja to MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'trashed_at')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->timestamp('trashed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'trashed_at')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('trashed_at'));
        }
    }
};
