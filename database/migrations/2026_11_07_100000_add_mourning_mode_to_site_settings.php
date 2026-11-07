<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tryb żałobny: witryna publiczna w odcieniach szarości (Ustawienia → Serwis). */
    public function up(): void
    {
        if (! Schema::hasColumn('site_settings', 'mourning_mode')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->boolean('mourning_mode')->default(false);
                $table->date('mourning_from')->nullable();
                $table->date('mourning_until')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_settings', 'mourning_mode')) {
            Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['mourning_mode', 'mourning_from', 'mourning_until']));
        }
    }
};
