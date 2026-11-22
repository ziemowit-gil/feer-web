<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opis zdjęcia slajdu dla czytników ekranu (alt). Puste = zdjęcie dekoracyjne. Kod działa i bez tej migracji
 * (HeroSlide::supportsImageAlt()); szablony czytają `$slide->image_alt ?? ''`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('hero_slides', 'image_alt')) {
            return;
        }

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('image_alt')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hero_slides', 'image_alt')) {
            return;
        }

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn('image_alt');
        });
    }
};
