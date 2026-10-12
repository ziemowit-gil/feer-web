<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Matomo: adres instancji, ID witryny i tryb bez cookies (bez zgody RODO). */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('matomo_url', 255)->nullable()->after('ga_measurement_id');
            $table->string('matomo_site_id', 16)->nullable()->after('matomo_url');
            $table->boolean('matomo_cookieless')->default(true)->after('matomo_site_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['matomo_url', 'matomo_site_id', 'matomo_cookieless']));
    }
};
