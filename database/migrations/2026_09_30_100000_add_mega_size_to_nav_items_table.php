<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Wielkość pozycji w panelu mega menu (NavItem::MEGA_SIZES): sm | md | lg. */
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->string('mega_size', 10)->default('md')->after('is_mega');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn('mega_size');
        });
    }
};
