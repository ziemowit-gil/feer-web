<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Godziny dostępności numeru telefonu na stronie kontaktu (np. „czw–pt 10:00–16:00”). */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'contact_phone_hours')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->string('contact_phone_hours', 160)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'contact_phone_hours')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('contact_phone_hours'));
        }
    }
};
