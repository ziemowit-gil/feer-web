<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Treści strony darowizny: nagłówek, opisy efektu kwot, „na co przeznaczamy”, link do sprawozdań. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('donation_headline')->nullable();
            $table->text('donation_impacts')->nullable();
            $table->text('donation_use_note')->nullable();
            $table->string('donation_reports_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['donation_headline', 'donation_impacts', 'donation_use_note', 'donation_reports_url']);
        });
    }
};
