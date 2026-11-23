<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Informacja o plikach cookies (Ustawienia → Ogólne → Pliki cookies): własny wstęp strony /informacja-o-cookies
 * i adres polityki prywatności (dotąd wpisany na sztywno /polityka-prywatnosci). Kod działa bez tych kolumn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'cookie_info_text')) {
                $table->text('cookie_info_text')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'privacy_policy_url')) {
                $table->string('privacy_policy_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['cookie_info_text', 'privacy_policy_url'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
