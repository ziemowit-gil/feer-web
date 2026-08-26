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
            // Wyróżniona uwaga o kierowaniu korespondencji, np. „Pisma urzędowe
            // kierujcie na e-Doręczenia, a nie na adres biura”. Pokazywana na
            // samej górze podstrony /kontakt, nad formularzem.
            $table->string('contact_correspondence_title')->nullable();
            $table->text('contact_correspondence_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_correspondence_title', 'contact_correspondence_note']);
        });
    }
};
