<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Opcja: duża wyszukiwarka na stronie głównej (szablon FEER). */
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->boolean('home_search_enabled')->default(true));
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('home_search_enabled'));
    }
};
