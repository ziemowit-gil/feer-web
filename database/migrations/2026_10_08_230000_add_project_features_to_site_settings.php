<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Rozszerzenia modułu projektów włączane w panelu (domyślnie wyłączone).
            $table->boolean('projects_stages_enabled')->default(false);
            $table->boolean('projects_team_funding_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['projects_stages_enabled', 'projects_team_funding_enabled']));
    }
};
