<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mikro moduł „Osoby – Kreatywni” (szablon „kreatywni”): osoby z sekcji „Kto prowadzi warsztaty” na stronie „O nas”
 * i w sekcji Zespół na stronie głównej. Zdjęcie w kolekcji mediów „photo”. Bez tej tabeli szablon bierze osoby
 * z tekstów (KreatywniTexts: about_team, team_items).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kreatywni_people')) {
            return;
        }

        Schema::create('kreatywni_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('name');
            $table->string('role')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_alt')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kreatywni_people');
    }
};
