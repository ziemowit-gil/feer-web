<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Zaszyfrowany klucz API GetResponse do importu subskrybentów newslettera. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'newsletter_getresponse_token')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->text('newsletter_getresponse_token')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'newsletter_getresponse_token')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('newsletter_getresponse_token');
            });
        }
    }
};
