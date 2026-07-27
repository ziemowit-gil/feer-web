<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FAQ do szkoleń/wydarzeń jako osobny typ (pytanie + odpowiedź), powiązany
     * z konkretnym wydarzeniem. Renderowane jako dostępny akordeon na stronie
     * wydarzenia.
     */
    public function up(): void
    {
        Schema::create('event_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_faqs');
    }
};
