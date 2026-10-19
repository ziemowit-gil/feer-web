<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Styl menu bocznego panelu wybierany w Mój profil (User::ADMIN_NAV_STYLES). Bez after() — produkcja to MySQL. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_nav_style')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('admin_nav_style', 20)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_nav_style')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('admin_nav_style'));
        }
    }
};
