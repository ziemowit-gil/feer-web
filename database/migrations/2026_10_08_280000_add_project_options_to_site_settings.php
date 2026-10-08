<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_info_page_id')->nullable();       // strona z pełnym objaśnieniem (link „Przeczytaj więcej")
            $table->boolean('projects_terms_enabled')->default(true);         // „Kto może wziąć udział" (warunki udziału)
            $table->boolean('projects_subprojects_enabled')->default(true);   // podprojekty (formy udziału)
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['paid_info_page_id', 'projects_terms_enabled', 'projects_subprojects_enabled']));
    }
};
