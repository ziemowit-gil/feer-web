<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mikro moduł „Warsztaty – Kreatywni” (szablon „kreatywni”): terminy warsztatów do strony typu „Kalendarz warsztatów”
 * — nazwa, miejsce, początek i koniec (UTC; w panelu czas polski), liczba uczestników, opis i grafika ilustracyjna
 * (kolekcja mediów „image”, opis alternatywny w image_alt).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kreatywni_workshops')) {
            return;
        }

        Schema::create('kreatywni_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('title');
            $table->string('place')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->string('participants', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('image_alt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kreatywni_workshops');
    }
};
