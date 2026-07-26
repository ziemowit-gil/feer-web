<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Główny adres strony (Site URL) — nadpisuje APP_URL z .env w runtime.
            $table->string('site_url')->nullable();
            // Globalny tryb konserwacji (przerwa techniczna).
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['site_url', 'maintenance_mode', 'maintenance_message']);
        });
    }
};
