<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Własny nagłówek dodatkowej kolumny w menu projektów (domyślnie „To już zrobiliśmy"). */
    public function up(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->string('mega_extra_title', 80)->nullable()->after('mega_size');
        });
    }

    public function down(): void
    {
        Schema::table('nav_items', function (Blueprint $table) {
            $table->dropColumn('mega_extra_title');
        });
    }
};
