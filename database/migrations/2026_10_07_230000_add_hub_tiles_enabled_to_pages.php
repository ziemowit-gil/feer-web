<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'hub_tiles_enabled')) {
            Schema::table('pages', function (Blueprint $table) {
                // „Kafelki na stronie — włącz": wyłączone ukrywa kafelki strony typu hub (ręczne i z podstron). Domyślnie włączone.
                $table->boolean('hub_tiles_enabled')->default(true)->after('hub_links');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'hub_tiles_enabled')) {
            Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('hub_tiles_enabled'));
        }
    }
};
