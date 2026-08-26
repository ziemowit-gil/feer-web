<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Nazwa budynku/biurowca (np. „Biurowiec HEXAGON") oraz opis alternatywny
            // zdjęcia biura — samo zdjęcie trzyma media library (kolekcja office_photo).
            $table->string('contact_office_building')->nullable()->after('contact_office_city');
            $table->string('contact_office_photo_alt')->nullable()->after('contact_office_note');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['contact_office_building', 'contact_office_photo_alt']);
        });
    }
};
