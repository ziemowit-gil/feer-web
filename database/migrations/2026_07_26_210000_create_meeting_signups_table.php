<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zgłoszenia z formularza „Daj znać, że przyjdziesz” na podstronie /kontakt.
        Schema::create('meeting_signups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('term')->nullable();   // którego terminu dotyczy (opcjonalnie)
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_signups');
    }
};
