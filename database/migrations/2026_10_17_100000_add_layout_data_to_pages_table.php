<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ustawienia szablonu wizualnego strony (np. „Strona działu": kategoria
 * i liczba aktualności, slider). Osobno od `type_data`, które należy do typu strony.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'layout_data')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->json('layout_data')->nullable()->after('page_template');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'layout_data')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('layout_data');
            });
        }
    }
};
