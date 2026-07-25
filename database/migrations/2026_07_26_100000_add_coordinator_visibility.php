<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widoczność koordynatora: przełącznik per-projekt oraz globalny wyłącznik
     * w ustawieniach serwisu. Koordynator jest widoczny tylko wtedy, gdy oba
     * są włączone.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('show_coordinator')->default(true)->after('is_featured_contact');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('show_coordinators')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('show_coordinator');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('show_coordinators');
        });
    }
};
