<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dodatkowe kolory identyfikacji marki (FEER ma 4 kolory): 2., 3. i 4.
     * korespondujące z kolorem głównym (brand_color). Puste = niewykorzystane.
     * Kontrast pilnujemy przy zapisie (AA na białym).
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('brand_color_2')->nullable()->after('brand_color');
            $table->string('brand_color_3')->nullable()->after('brand_color_2');
            $table->string('brand_color_4')->nullable()->after('brand_color_3');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['brand_color_2', 'brand_color_3', 'brand_color_4']);
        });
    }
};
