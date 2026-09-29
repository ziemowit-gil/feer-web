<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Strona „Darowizna jednorazowa": proponowane kwoty i tekst wstępu. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('donation_amounts')->nullable();
            $table->text('donation_intro')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['donation_amounts', 'donation_intro']);
        });
    }
};
