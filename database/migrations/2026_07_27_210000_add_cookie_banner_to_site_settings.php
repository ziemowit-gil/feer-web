<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Baner cookies: przełącznik widoczności + opcjonalny własny tekst. */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('cookie_banner_enabled')->default(true)->after('unsplash_access_key');
            $table->text('cookie_banner_text')->nullable()->after('cookie_banner_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['cookie_banner_enabled', 'cookie_banner_text']);
        });
    }
};
