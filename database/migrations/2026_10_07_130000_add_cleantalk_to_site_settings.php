<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ochrona antyspamowa CleanTalk: włącznik i klucz dostępu (szyfrowany) konfigurowane w panelu. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('cleantalk_enabled')->nullable();
            $table->text('cleantalk_access_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['cleantalk_enabled', 'cleantalk_access_key']);
        });
    }
};
