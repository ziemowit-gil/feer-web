<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Opcja: pokazuj w BIP sprawozdania roczne z modułu „Sprawozdania". */
    public function up(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->boolean('bip_show_reports')->default(true));
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('bip_show_reports'));
    }
};
