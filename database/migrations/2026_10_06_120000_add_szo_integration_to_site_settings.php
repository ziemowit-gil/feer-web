<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integracja z SZO konfigurowana z panelu (dotąd wyłącznie przez .env):
 * włącznik przekazywania zgłoszeń, token API, domyślne formularze i limit czasu.
 * Adres bazowy to istniejące pole `szo_api_url`. Puste wartości dziedziczą z .env.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('szo_enabled')->nullable();
            $table->text('szo_token')->nullable();
            $table->string('szo_default_form', 120)->nullable();
            $table->string('szo_donation_form', 120)->nullable();
            $table->unsignedSmallInteger('szo_timeout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['szo_enabled', 'szo_token', 'szo_default_form', 'szo_donation_form', 'szo_timeout']);
        });
    }
};
