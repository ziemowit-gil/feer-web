<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose content can be scoped to a specific site (the main
     * federation site or one of its sub-sites). Nullable + backfilled to the
     * main site (id 1), mirroring the existing nullable `project_id` FK on
     * `pages`/`news`.
     */
    private const TABLES = [
        'news', 'pages', 'gallery_images', 'events',
        'partners', 'quick_actions', 'polls', 'hero_slides',
    ];

    public function up(): void
    {
        // Świeża instalacja nie ma jeszcze wiersza głównej witryny (id 1) — powstaje
        // leniwie przez SiteSetting::current() przy pierwszym żądaniu/instalatorze.
        // Bez tego backfill niżej złamałby FK na pustej bazie (patrz seed-migracje
        // typu 2026_08_10_..._seed_wspolpraca_and_dolacz_pages, które już tu wstawiają
        // wiersze do `pages` przed tą migracją).
        if (! DB::table('site_settings')->where('id', 1)->exists()) {
            DB::table('site_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('site_id')->nullable()->after('id')
                    ->constrained('site_settings')->restrictOnDelete();
            });

            DB::table($table)->whereNull('site_id')->update(['site_id' => 1]);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('site_id');
            });
        }
    }
};
