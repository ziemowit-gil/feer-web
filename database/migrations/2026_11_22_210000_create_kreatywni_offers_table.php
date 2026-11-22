<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mikro moduł „Mikrooferta – Kreatywni” (szablon „kreatywni”): oferty z bloku sprzedażowego na stronie głównej
 * i na górze strony „Oferta” — karta cenowa (nazwa, cena, jednostka, dopisek, korzyści, przycisk) i warsztaty
 * w grupach („Tytuł | grupa”) w wyglądzie pigułek albo zwykłej listy. Bez tej tabeli szablon bierze ofertę
 * z tekstów (KreatywniTexts: offer_*).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kreatywni_offers')) {
            return;
        }

        Schema::create('kreatywni_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('name');
            $table->string('price', 100)->nullable();
            $table->string('price_unit', 100)->nullable();
            $table->string('price_note')->nullable();
            $table->text('features')->nullable();
            $table->text('items')->nullable();
            $table->string('items_style', 20)->default('chips');
            $table->string('button_label', 100)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kreatywni_offers');
    }
};
