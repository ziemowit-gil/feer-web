<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('quick_facts')->nullable();   // {duration, place, mode, seats}
            $table->text('easy_summary')->nullable();  // streszczenie prostym językiem (ETR)
            $table->json('main_cta')->nullable();      // {label, url} — główny przycisk
            $table->json('metrics')->nullable();       // [{value, label}] wskaźniki rezultatów
            $table->json('testimonials')->nullable();  // [{text, author}] opinie uczestników
            $table->json('faq')->nullable();           // [{q, a}] pytania i odpowiedzi
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('projects_extras_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('projects_extras_enabled'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['quick_facts', 'easy_summary', 'main_cta', 'metrics', 'testimonials', 'faq']));
    }
};
