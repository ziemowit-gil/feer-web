<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wariant motywu FEER (podmodel szablonu): ten sam nagłówek, stopka i podstrony, inny układ strony głównej
 * „FEER IS” (portal usług w układzie gov.pl). Dla innych witryn sieci wybierany w Ustawienia → Szablon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'theme_variant')) {
                $table->string('theme_variant', 30)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'services_tabs')) {
                $table->text('services_tabs')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'services_all_label')) {
                $table->string('services_all_label', 80)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'services_all_url')) {
                $table->string('services_all_url', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['theme_variant', 'services_tabs', 'services_all_label', 'services_all_url'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
