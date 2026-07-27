<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Osoba prowadząca wydarzenie: imię i nazwisko, rola/tytuł oraz krótkie bio.
     * Zdjęcie prowadzącej trzymamy w media-library (kolekcja facilitator_photo),
     * więc nie ma tu kolumny na plik.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('facilitator_name')->nullable()->after('description');
            $table->string('facilitator_role')->nullable()->after('facilitator_name');
            $table->text('facilitator_bio')->nullable()->after('facilitator_role');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['facilitator_name', 'facilitator_role', 'facilitator_bio']);
        });
    }
};
