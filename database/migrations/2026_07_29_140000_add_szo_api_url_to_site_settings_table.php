<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Adres systemu SZO (bazowy URL). Strefa współpracownika pobiera z niego
            // komunikaty przez GET {szo_api_url}/api/komunikaty/list.php. Puste = brak
            // integracji (strefa nie pokazuje wtedy komunikatów).
            $table->string('szo_api_url')->nullable()->after('member_allowed_domains');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('szo_api_url');
        });
    }
};
