<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Schemat kolorów panelu wybierany w Mój profil (User::ADMIN_SCHEMES). Bez after() — produkcja to MySQL. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'admin_scheme')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('admin_scheme', 20)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'admin_scheme')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('admin_scheme'));
        }
    }
};
