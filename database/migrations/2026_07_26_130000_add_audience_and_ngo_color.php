<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grupa docelowa projektu/newsa steruje kolorystyką: "brand" (kolor marki,
     * domyślnie) lub "ngo" (dedykowany, konfigurowalny kolor w ustawieniach).
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('audience')->default('brand')->after('for_whom');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->string('audience')->default('brand')->after('excerpt');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('ngo_color')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('audience');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('audience');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('ngo_color');
        });
    }
};
