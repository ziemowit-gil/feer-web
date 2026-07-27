<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Klucz Unsplash Access Key konfigurowalny z panelu (fallback do .env). */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('unsplash_access_key')->nullable()->after('microsoft_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('unsplash_access_key');
        });
    }
};
