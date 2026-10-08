<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('project_sections_nav', 10)->nullable(); // 'tabs' (domyślnie) | 'sidebar' — dla całego serwisu
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->string('sections_nav', 10)->nullable(); // null = jak w ustawieniach serwisu
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('project_sections_nav'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('sections_nav'));
    }
};
