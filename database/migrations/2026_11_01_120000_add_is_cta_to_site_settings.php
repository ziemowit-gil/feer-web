<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edytowalny przycisk w nagłówku wariantu FEER IS (zamiast „Materiały edukacyjne”): napis i adres z ustawień witryny.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'is_cta_label')) {
                $table->string('is_cta_label', 60)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'is_cta_url')) {
                $table->string('is_cta_url', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['is_cta_label', 'is_cta_url'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
