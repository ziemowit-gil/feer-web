<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytor blokowy dla aktualności i działań — struktura bloków (JSON) obok
 * `content`, tak jak w `pages.blocks`. Front korzysta dalej z `content`.
 */
return new class extends Migration
{
    private const TABLES = ['news', 'projects'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasTable($name) && ! Schema::hasColumn($name, 'blocks')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->json('blocks')->nullable()->after('content');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'blocks')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropColumn('blocks');
                });
            }
        }
    }
};
