<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pola strony typu "O organizacji": motto z autorem oraz powtarzalne sekcje
     * (statystyki, oś czasu, wartości/kafelki, zespół) trzymane jako JSON.
     * Zdjęcia galerii mają własną tabelę page_images (osobna migracja).
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('about_motto')->nullable()->after('schedule_pending');
            $table->string('about_motto_author')->nullable()->after('about_motto');
            $table->json('about_stats')->nullable()->after('about_motto_author');     // [{value, label}]
            $table->json('about_timeline')->nullable()->after('about_stats');         // [{year, text}]
            $table->json('about_values')->nullable()->after('about_timeline');        // [{icon, title, text}]
            $table->json('about_team')->nullable()->after('about_values');            // [{name, role, photo}]
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['about_motto', 'about_motto_author', 'about_stats', 'about_timeline', 'about_values', 'about_team']);
        });
    }
};
