<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Metryczka: czy w ogóle pokazywać „Podmiot publikujący” (dla całego serwisu). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->boolean('metka_show_entity')->default(true));
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('metka_show_entity'));
    }
};
