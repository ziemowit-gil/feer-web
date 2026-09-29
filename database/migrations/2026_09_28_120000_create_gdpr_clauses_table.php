<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lokalna kopia klauzul informacyjnych RODO z systemu SZO (moduł „Klauzule RODO”).
 * Źródłem prawdy jest SZO — tu trzymamy migawkę z importu (`szo:import-clauses`),
 * żeby strona /rodo działała także wtedy, gdy SZO jest niedostępne.
 * Lokalnie edytowalne są tylko: widoczność na liście i kolejność.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_clauses', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64);
            $table->string('lang', 5)->default('pl');
            $table->string('title');
            $table->longText('html');
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('remote_updated_at')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('pdf_url', 500)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'lang']);
            $table->index(['lang', 'is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_clauses');
    }
};
