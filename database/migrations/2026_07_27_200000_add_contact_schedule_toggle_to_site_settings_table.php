<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Przełącznik wyboru terminu spotkania na stronie kontakt. Gdy wyłączony,
     * zamiast harmonogramu i przycisków pokazujemy komunikat (edytowalny), np.
     * „Jeszcze nie ustaliliśmy żadnych terminów".
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('contact_schedule_enabled')->default(true)->after('contact_schedule');
            $table->string('contact_no_schedule_note')->nullable()->after('contact_schedule_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_schedule_enabled', 'contact_no_schedule_note']);
        });
    }
};
