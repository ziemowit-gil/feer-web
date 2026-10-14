<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portal kategorii działań: /portal/{portal_slug} (i subdomena {portal_slug}.feer.org.pl → przekierowanie).
 * Slider, wstęp, widżety (kolejność i włączenie) oraz zewnętrzny kanał RSS ustawiane w panelu kategorii.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('portal_slug', 63)->nullable()->unique()->after('slug');
            $table->string('portal_title')->nullable()->after('portal_slug');
            $table->text('portal_intro')->nullable()->after('portal_title');
            $table->unsignedBigInteger('portal_slider_id')->nullable()->after('portal_intro');
            $table->string('portal_rss_url', 500)->nullable()->after('portal_slider_id');
            $table->json('portal_widgets')->nullable()->after('portal_rss_url');
        });

        // Dwa pierwsze portale (szkolenia.feer.org.pl, edukacja.feer.org.pl) — o ile kategorie istnieją i adres jest wolny.
        foreach (['szkolenia-i-warsztaty' => 'szkolenia', 'edukacja-i-materialy' => 'edukacja'] as $categorySlug => $portalSlug) {
            if (! DB::table('categories')->where('portal_slug', $portalSlug)->exists()) {
                DB::table('categories')->where('slug', $categorySlug)->whereNull('portal_slug')->update(['portal_slug' => $portalSlug]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['portal_slug']);
            $table->dropColumn(['portal_slug', 'portal_title', 'portal_intro', 'portal_slider_id', 'portal_rss_url', 'portal_widgets']);
        });
    }
};
