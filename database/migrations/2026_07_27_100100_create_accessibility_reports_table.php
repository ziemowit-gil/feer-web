<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zgłoszenia barier dostępności z formularza na stronie deklaracji
     * dostępności (element wymagany ustawą: mechanizm informacji zwrotnej).
     */
    public function up(): void
    {
        Schema::create('accessibility_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('page_url')->nullable();   // której strony/elementu dotyczy bariera
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessibility_reports');
    }
};
