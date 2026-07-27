<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Projekt odpłatny + cennik (lista pozycji: nazwa, cena, opis). */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('is_completed');
            $table->json('pricing')->nullable()->after('is_paid'); // [{item, price, note}]
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'pricing']);
        });
    }
};
