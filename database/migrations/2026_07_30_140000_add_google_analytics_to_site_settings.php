<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Identyfikator pomiaru Google Analytics 4 (np. G-XXXXXXXXXX). */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('ga_measurement_id', 32)->nullable()->after('allow_indexing');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('ga_measurement_id'));
    }
};
