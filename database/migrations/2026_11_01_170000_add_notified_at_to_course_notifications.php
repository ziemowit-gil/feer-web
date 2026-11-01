<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kiedy wysłano powiadomienie o nowym terminie (jedna wiadomość na zapis, bez ponownej wysyłki). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_notifications') && ! Schema::hasColumn('course_notifications', 'notified_at')) {
            Schema::table('course_notifications', fn (Blueprint $table) => $table->timestamp('notified_at')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_notifications', 'notified_at')) {
            Schema::table('course_notifications', fn (Blueprint $table) => $table->dropColumn('notified_at'));
        }
    }
};
