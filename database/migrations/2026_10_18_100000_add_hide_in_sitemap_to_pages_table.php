<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * „Ukryj na mapie strony" — strona pozostaje dostępna pod swoim adresem, ale nie pojawia się
 * na /mapa-strony ani w sitemap.xml. Jednorazowo zaznacza strony testowe (adres test, test3, tes4e…).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pages', 'hide_in_sitemap')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->boolean('hide_in_sitemap')->default(false)->after('show_in_menu');
            });
        }

        $testIds = DB::table('pages')->get(['id', 'slug'])
            ->filter(fn ($p) => preg_match('/^(test\d*|tes\d+[a-z]?)$/i', (string) $p->slug))
            ->pluck('id');
        if ($testIds->isNotEmpty()) {
            DB::table('pages')->whereIn('id', $testIds)->update(['hide_in_sitemap' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'hide_in_sitemap')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('hide_in_sitemap');
            });
        }
    }
};
