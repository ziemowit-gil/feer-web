<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolor akcentu sekcji „Szkolenia i wydarzenia" na stronie głównej. Pusty =
     * kolor marki. Pozwala wyróżnić moduł wydarzeń innym kolorem z identyfikacji.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('events_home_color')->nullable()->after('homepage_section_order');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('events_home_color');
        });
    }
};
