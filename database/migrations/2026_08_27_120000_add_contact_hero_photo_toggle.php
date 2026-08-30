<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Czy wgrane zdjęcie biura ma iść w tło nagłówka strony kontaktowej.
            $table->boolean('contact_hero_photo')->default(true)->after('contact_office_photo_alt');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('contact_hero_photo');
        });
    }
};
