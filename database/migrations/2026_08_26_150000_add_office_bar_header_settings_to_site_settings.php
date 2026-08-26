<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Pasek informacyjny (data / imieniny) — wspólny dla szablonu gminnego
            // i substylu „urzędowego" nagłówka FEER.
            $table->boolean('infobar_show_date')->default(true);
            $table->boolean('infobar_show_nameday')->default(true);

            // Substyl „urzędowy": co pokazać w górnej belce.
            $table->boolean('office_show_account')->default(true);
            $table->boolean('office_show_search')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'infobar_show_date', 'infobar_show_nameday',
                'office_show_account', 'office_show_search',
            ]);
        });
    }
};
