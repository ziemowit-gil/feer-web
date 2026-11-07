<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Moduł Kontakt: opcje strony kontaktu w jednym polu JSON (formularz na stronie, podstrony, dojazd, komunikat, vCard). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'contact_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->json('contact_options')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'contact_options')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('contact_options');
            });
        }
    }
};
