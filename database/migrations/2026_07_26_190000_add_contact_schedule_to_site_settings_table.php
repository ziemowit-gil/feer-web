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
            // Sekcja „Spotkajmy się” na podstronie /kontakt: dwie drogi kontaktu
            // osobistego — spotkanie online (link do rezerwacji dogodnego terminu)
            // oraz harmonogram stacjonarny (kiedy i gdzie jesteśmy, np. w Krakowie).
            $table->string('contact_meeting_title')->nullable();
            $table->string('contact_online_meeting_url')->nullable();
            $table->string('contact_online_meeting_label')->nullable();
            $table->string('contact_online_meeting_text')->nullable();
            $table->string('contact_schedule_title')->nullable();
            // Lista wpisów harmonogramu jako JSON (kiedy / gdzie / dopisek).
            $table->json('contact_schedule')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_meeting_title',
                'contact_online_meeting_url',
                'contact_online_meeting_label',
                'contact_online_meeting_text',
                'contact_schedule_title',
                'contact_schedule',
            ]);
        });
    }
};
