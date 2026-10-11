<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BIP jako witryna sieci: rodzaj witryny (`site_kind` — zwykła albo BIP)
 * i przypisanie dokumentów BIP do witryny (`bip_documents.site_id`), tak jak
 * reszta treści skopowanej przez BelongsToSite. Istniejące dokumenty trafiają
 * do głównej witryny.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'site_kind')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('site_kind', 20)->default('standard')->after('parent_site_id');
            });
        }

        if (! Schema::hasColumn('bip_documents', 'site_id')) {
            Schema::table('bip_documents', function (Blueprint $table) {
                $table->unsignedBigInteger('site_id')->nullable()->after('id')->index();
            });

            $mainId = DB::table('site_settings')->whereNull('parent_site_id')->orderBy('id')->value('id') ?? 1;
            DB::table('bip_documents')->whereNull('site_id')->update(['site_id' => $mainId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bip_documents', 'site_id')) {
            Schema::table('bip_documents', function (Blueprint $table) {
                $table->dropIndex(['site_id']);
                $table->dropColumn('site_id');
            });
        }

        if (Schema::hasColumn('site_settings', 'site_kind')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('site_kind');
            });
        }
    }
};
