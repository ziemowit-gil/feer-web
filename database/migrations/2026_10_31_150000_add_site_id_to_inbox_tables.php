<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zgłoszenia z formularzy, wiadomości kontaktowe i zapisy na materiały zapamiętują witrynę (główną albo podrzędną),
 * z której przyszły, żeby skrzynki w panelu były osobne dla każdej witryny. Istniejące wpisy trafiają do witryny głównej.
 */
return new class extends Migration
{
    private const TABLES = ['form_submissions', 'contact_messages', 'material_subscribers'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'site_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('site_id')->nullable()->index();
                });
            }
        }

        // Ten sam adres e-mail może zapisać się na materiały w każdej witrynie osobno.
        if (Schema::hasTable('material_subscribers') && Schema::hasColumn('material_subscribers', 'site_id')) {
            try {
                Schema::table('material_subscribers', function (Blueprint $t) {
                    $t->dropUnique(['email']);
                });
            } catch (\Throwable) {
            }
            try {
                Schema::table('material_subscribers', function (Blueprint $t) {
                    $t->unique(['site_id', 'email']);
                });
            } catch (\Throwable) {
            }
        }

        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $mainId = DB::table('site_settings')->whereNull('parent_site_id')->orderBy('id')->value('id')
            ?? DB::table('site_settings')->orderBy('id')->value('id');

        if ($mainId) {
            foreach (self::TABLES as $table) {
                if (Schema::hasColumn($table, 'site_id')) {
                    DB::table($table)->whereNull('site_id')->update(['site_id' => $mainId]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('material_subscribers')) {
            try {
                Schema::table('material_subscribers', function (Blueprint $t) {
                    $t->dropUnique(['site_id', 'email']);
                });
            } catch (\Throwable) {
            }
        }

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'site_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('site_id'));
            }
        }
    }
};
