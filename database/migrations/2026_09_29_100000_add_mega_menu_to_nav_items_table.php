<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mega menu: pozycja „Rozwijane menu" lub link z podstronami może otworzyć
     * panel na całą szerokość paska (kolumny z ikoną i opisem) zamiast wąskiej
     * listy. `description` to krótki opis podpozycji pokazywany w mega menu.
     */
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->boolean('is_mega')->default(false)->after('is_transparent_dropdown');
            $table->string('description')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn(['is_mega', 'description']);
        });
    }
};
