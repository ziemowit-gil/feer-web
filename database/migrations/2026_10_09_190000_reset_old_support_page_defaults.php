<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teksty strony /wsparcie, które były zapisane w bazie dokładnie w dawnej wersji domyślnej, wracają do wartości
     * domyślnej (null) — dzięki temu pokażą się nowe teksty o misji FEER. Teksty zmienione ręcznie zostają.
     */
    public function up(): void
    {
        $old = [
            'support_hero_title' => 'Twoje wsparcie tworzy świat bez barier cyfrowych',
            'support_hero_subtitle' => 'Dzięki Tobie więcej osób zyska dostęp do wiedzy i niezależności. Każda forma wsparcia realnie napędza nasze działania.',
            'support_benefits_title' => 'Dlaczego warto nas wspierać',
            'support_benefits_subtitle' => 'Działamy na rzecz dostępności cyfrowej i edukacji. Oto, co umożliwia Twoje wsparcie.',
            'support_benefit1_title' => 'Dostępność dla każdego',
            'support_benefit1_text' => 'Usuwamy bariery cyfrowe, aby z internetu mogły swobodnie korzystać osoby z niepełnosprawnościami.',
            'support_benefit1_icon' => 'fa-solid fa-universal-access',
            'support_benefit2_title' => 'Edukacja i narzędzia',
            'support_benefit2_text' => 'Finansujemy szkolenia, audyty WCAG oraz otwarte narzędzia dostępne bezpłatnie dla wszystkich.',
            'support_benefit2_icon' => 'fa-solid fa-graduation-cap',
            'support_benefit3_title' => 'Niezależność działań',
            'support_benefit3_text' => 'Darowizny pozwalają nam działać niezależnie i reagować tam, gdzie wsparcie jest najbardziej potrzebne.',
            'support_benefit3_icon' => 'fa-solid fa-hand-holding-heart',
            'support_outro_title' => 'Każda złotówka przybliża nas do świata bez barier.',
            'support_outro_subtitle' => 'Dziękujemy, że jesteś częścią tej zmiany.',
        ];

        foreach ($old as $column => $value) {
            if (Schema::hasColumn('site_settings', $column)) {
                DB::table('site_settings')->where($column, $value)->update([$column => null]);
            }
        }
    }

    public function down(): void
    {
    }
};
