<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ogłoszenie wolontariatu może mieć drugi przycisk — link do umówienia spotkania online
 * (np. TidyCal, Calendly, Bookings) — obok przycisku zgłoszenia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->string('meeting_url', 500)->nullable()->after('form_definition_id');
            $table->string('meeting_cta_label', 60)->nullable()->after('meeting_url');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_ads', function (Blueprint $table) {
            $table->dropColumn(['meeting_url', 'meeting_cta_label']);
        });
    }
};
