<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('paid_info_show')->default(true); // pokaż objaśnienie odpłatnej działalności pożytku publicznego
            $table->text('paid_info_text')->nullable();       // własny tekst (puste = tekst z ustawień serwisu)
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['paid_info_show', 'paid_info_text']));
    }
};
