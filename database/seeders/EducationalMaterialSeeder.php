<?php

namespace Database\Seeders;

use App\Models\EducationalMaterial;
use Illuminate\Database\Seeder;

class EducationalMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materials = [
            [
                'title'        => 'WCAG 2.2 – wprowadzenie dla twórców stron',
                'description'  => 'Omówienie kluczowych kryteriów sukcesu WCAG 2.2 ze szczególnym uwzględnieniem zmian względem WCAG 2.1. Materiał skierowany do webmasterów i redaktorów CMS.',
                'target_group' => 'Webmasterzy, redaktorzy CMS, specjaliści UX',
                'type'         => 'video',
                'video_url'    => 'https://www.youtube.com/watch?v=20SHvU2PKsM',
                'order'        => 1,
                'is_published' => true,
                'is_archival'  => false,
            ],
            [
                'title'        => 'Dostępne dokumenty PDF – poradnik krok po kroku',
                'description'  => 'Praktyczny przewodnik po tworzeniu i remediacji plików PDF pod kątem dostępności cyfrowej. Zawiera checklistę i wzorcowy szablon dokumentu.',
                'target_group' => 'Pracownicy biurowi, redaktorzy, sekretarze',
                'type'         => 'pdf',
                'video_url'    => null,
                'order'        => 2,
                'is_published' => true,
                'is_archival'  => false,
            ],
            [
                'title'        => 'Scenariusz zajęć: Dostępność cyfrowa w szkole',
                'description'  => 'Gotowy scenariusz lekcji dla uczniów szkół ponadpodstawowych. Obejmuje ćwiczenia praktyczne z nawigacji klawiaturą i korzystania z czytnika ekranu.',
                'target_group' => 'Nauczyciele informatyki i edukacji medialnej (szkoły ponadpodstawowe)',
                'type'         => 'scenariusz',
                'video_url'    => null,
                'order'        => 3,
                'is_published' => true,
                'is_archival'  => false,
            ],
            [
                'title'        => 'Kontrast i kolory w projektowaniu dostępnym',
                'description'  => 'Webinar nagrany z cyklu szkoleń online. Omawia wymagania WCAG dotyczące kontrastu (1.4.3 i 1.4.11), narzędzia do sprawdzania kontrastu i typowe błędy projektowe.',
                'target_group' => 'Graficy, projektanci UI, twórcy materiałów wizualnych',
                'type'         => 'video',
                'video_url'    => 'https://www.youtube.com/watch?v=Y68eSFkgkXk',
                'order'        => 4,
                'is_published' => true,
                'is_archival'  => false,
            ],
            [
                'title'        => 'Dostępność formularzy HTML – checklisty i przykłady',
                'description'  => 'Zestaw checklist i przykładów kodu HTML do tworzenia dostępnych formularzy internetowych zgodnie z WCAG 2.1/2.2.',
                'target_group' => 'Programiści frontend, twórcy stron WWW',
                'type'         => 'pdf',
                'video_url'    => null,
                'order'        => 5,
                'is_published' => true,
                'is_archival'  => false,
            ],
            [
                'title'        => 'WCAG 2.0 – wprowadzenie (archiwalne)',
                'description'  => 'Nagranie archiwalne z 2021 r. Dotyczy WCAG 2.0 – zalecamy korzystanie z nowszego materiału o WCAG 2.2.',
                'target_group' => 'Ogólny',
                'type'         => 'video',
                'video_url'    => 'https://www.youtube.com/watch?v=Agq6kUMBBzU',
                'order'        => 10,
                'is_published' => true,
                'is_archival'  => true,
            ],
        ];

        foreach ($materials as $data) {
            EducationalMaterial::updateOrCreate(
                ['title' => $data['title']],
                $data,
            );
        }
    }
}
