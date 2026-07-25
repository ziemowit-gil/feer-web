<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slugs of the mandatory legal / structural pages that ship with every
     * site and must not be deleted from the admin.
     */
    private const SYSTEM_SLUGS = [
        'deklaracja-dostepnosci',
        'polityka-prywatnosci',
        'mapa-strony',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('show_in_menu');
        });

        DB::table('pages')->whereIn('slug', self::SYSTEM_SLUGS)->update(['is_system' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
