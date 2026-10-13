<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * „Pokaż na stronie głównej” przy działaniu — strona główna pokazuje tylko zaznaczone działania.
 * Żeby po wdrożeniu nic nie zniknęło, zaznaczamy działania widoczne tam dotąd (3 pierwsze aktywne w każdej witrynie).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->boolean('show_on_home')->default(false));

        $sites = DB::table('projects')->distinct()->pluck('site_id');
        foreach ($sites as $siteId) {
            $ids = DB::table('projects')->where('site_id', $siteId)->where('is_published', true)->where('is_completed', false)
                ->orderBy('order')->limit(3)->pluck('id');
            DB::table('projects')->whereIn('id', $ids)->update(['show_on_home' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('show_on_home'));
    }
};
