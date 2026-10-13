<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metryczka strony („pokaż metkę”): autor (edytowalny), osoba publikująca (zapisywana automatycznie
 * przy publikacji, bez edycji), data pierwszej publikacji; w ustawieniach — gdzie metryczka jest włączona
 * (cały serwis / wybrane typy stron; strona może to nadpisać) i podmiot publikujący.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('metka_author', 160)->nullable();
            $table->foreignId('published_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_published_at')->nullable();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('metka_mode', 10)->default('off'); // off | all | types
            $table->json('metka_types')->nullable();
            $table->string('metka_entity', 255)->nullable();
            // Konta wspólne (np. strona@…): przy publikacji pytamy, kogo wskazać jako osobę publikującą.
            $table->text('metka_shared_accounts')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['published_by_id']);
            $table->dropColumn(['metka_author', 'published_by_id', 'first_published_at']);
        });
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['metka_mode', 'metka_types', 'metka_entity', 'metka_shared_accounts']));
    }
};
