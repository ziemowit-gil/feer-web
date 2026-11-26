<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ustawienia → Treści: „Wymuś boczne menu FEER (styl działań) na wszystkich podstronach z podmenu, poza działaniami”.
 * Kod działa i bez tej kolumny (Page::forcesPanelNav() czyta `force_panel_nav ?? false`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('site_settings', 'force_panel_nav')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('force_panel_nav')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('site_settings', 'force_panel_nav')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('force_panel_nav');
        });
    }
};
