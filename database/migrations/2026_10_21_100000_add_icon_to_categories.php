<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ikona kategorii działań (ozdobnik przy nagłówku kategorii na liście działań). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->string('icon', 80)->nullable());
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('icon'));
    }
};
