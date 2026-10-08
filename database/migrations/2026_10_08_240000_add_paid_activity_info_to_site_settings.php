<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('paid_activity_info')->nullable(); // własny tekst objaśnienia odpłatnej działalności pożytku publicznego
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('paid_activity_info'));
    }
};
