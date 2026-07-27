<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Krótka informacja/ciekawostka na /kontakt: na co dzień działamy zdalnie.
            $table->string('contact_remote_note')->nullable();
            // Adres, na który trafiają zgłoszenia „Daj znać, że przyjdziesz” oraz
            // kopia (DW) powiadomień o zmianie terminu. Pusty = użyj contact_email.
            $table->string('contact_meeting_notify_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_remote_note', 'contact_meeting_notify_email']);
        });
    }
};
