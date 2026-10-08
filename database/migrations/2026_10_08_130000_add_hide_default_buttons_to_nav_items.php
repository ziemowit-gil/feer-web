<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            // Menu projektów: domyślne przyciski „Wszystkie projekty" i „To już zrobiliśmy" są widoczne, chyba że wyłączone.
            $table->boolean('hide_all_projects_btn')->default(false);
            $table->boolean('hide_archive_btn')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', fn (Blueprint $table) => $table->dropColumn(['hide_all_projects_btn', 'hide_archive_btn']));
    }
};
