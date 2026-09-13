<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapowanie istniejących wolnotekstowych wartości `target_group` na nową,
     * zamkniętą listę — dopasowanie ręczne na podstawie treści (nie automat
     * słów kluczowych), bo docelowa lista jest krótka i każdy dotychczasowy
     * materiał dało się jednoznacznie przypisać.
     */
    private const TARGET_GROUP_MAP = [
        'Webmasterzy, redaktorzy CMS, specjaliści UX' => 'webmasterzy',
        'Pracownicy biurowi, redaktorzy, sekretarze' => 'pracownicy_biurowi',
        'Nauczyciele informatyki i edukacji medialnej (szkoły ponadpodstawowe)' => 'nauczyciele',
        'Graficy, projektanci UI, twórcy materiałów wizualnych' => 'graficy',
        'Programiści frontend, twórcy stron WWW' => 'programisci',
        'Ogólny' => 'ogolny',
    ];

    /** Kategoria tematyczna wg tytułu materiału (jednoznaczne dopasowanie treściowe). */
    private const CATEGORY_MAP = [
        'WCAG 2.2 – wprowadzenie dla twórców stron' => 'wcag',
        'Dostępne dokumenty PDF – poradnik krok po kroku' => 'dokumenty',
        'Scenariusz zajęć: Dostępność cyfrowa w szkole' => 'edukacja',
        'Kontrast i kolory w projektowaniu dostępnym' => 'projektowanie',
        'Dostępność formularzy HTML – checklisty i przykłady' => 'programowanie',
        'WCAG 2.0 – wprowadzenie (archiwalne)' => 'wcag',
    ];

    public function up(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
        });

        foreach (self::TARGET_GROUP_MAP as $free => $fixed) {
            DB::table('educational_materials')->where('target_group', $free)->update(['target_group' => $fixed]);
        }

        foreach (self::CATEGORY_MAP as $title => $category) {
            DB::table('educational_materials')->where('title', $title)->update(['category' => $category]);
        }

        // Wszystko, co nie zmapowało się jednoznacznie (nowe materiały dodane
        // między napisaniem tej migracji a jej uruchomieniem) — "ogólny", żeby
        // panel admina nie pokazywał pustej/nieprawidłowej wartości w select.
        DB::table('educational_materials')
            ->whereNotIn('target_group', array_values(self::TARGET_GROUP_MAP))
            ->update(['target_group' => 'ogolny']);
    }

    public function down(): void
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        $reverse = array_flip(self::TARGET_GROUP_MAP);
        foreach ($reverse as $fixed => $free) {
            DB::table('educational_materials')->where('target_group', $fixed)->update(['target_group' => $free]);
        }
    }
};
