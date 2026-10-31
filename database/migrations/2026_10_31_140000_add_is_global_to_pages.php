<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Podstrona globalna: strona oznaczona jako globalna jest dostępna pod swoim adresem we wszystkich witrynach sieci
 * (główna i sub-witryny), o ile dana witryna nie ma własnej strony o tym slugu. Slugi stron są i tak unikalne globalnie.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pages', 'is_global')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'is_global')) {
            Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('is_global'));
        }
    }
};
