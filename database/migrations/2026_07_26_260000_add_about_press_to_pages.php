<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sekcja „Piszą o nas" na stronie typu „O organizacji": wstęp oraz lista
     * wzmianek prasowych (link + tytuł + źródło + obrazek pobrany z og:image).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('about_press_intro')->nullable()->after('about_documents_bip_url');
            $table->json('about_press')->nullable()->after('about_press_intro');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['about_press_intro', 'about_press']);
        });
    }
};
