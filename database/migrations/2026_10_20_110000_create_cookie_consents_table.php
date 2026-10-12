<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rejestr decyzji z banera cookies (dowód zgody wg RODO, art. 7 ust. 1).
     * Bez pełnego IP: tylko zanonimizowany adres (wyzerowana końcówka).
     */
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('site_settings')->nullOnDelete();
            $table->string('consent_id', 36)->index();
            $table->string('decision', 16);
            $table->string('ip_anon', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
