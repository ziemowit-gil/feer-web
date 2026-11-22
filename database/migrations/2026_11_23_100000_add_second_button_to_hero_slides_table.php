<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drugi przycisk slajdu (np. „Oferta warsztatów” obok „Zobacz relacje”). Kod działa i bez tej migracji
 * (HeroSlide::supportsSecondButton()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('hero_slides', 'button2_label')) {
            return;
        }

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('button2_label')->nullable()->after('button_url');
            $table->string('button2_url')->nullable()->after('button2_label');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hero_slides', 'button2_label')) {
            return;
        }

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropColumn(['button2_label', 'button2_url']);
        });
    }
};
