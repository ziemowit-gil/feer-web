<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Po jednej przykładowej podstronie na każdy typ z Page::TYPES (poza
 * "about_person", zarządzanym przez osobny moduł "Osoby") — demonstracja
 * wszystkich dostępnych układów, podpięta pod rozwijane menu "Demo".
 */
class FederationPageTypesDemoSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(['slug' => 'demo-strona-standardowa'], [
            'title' => 'Demo: strona standardowa',
            'type' => 'standard',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Zwykła podstrona z dowolną treścią — nagłówki, akapity, obrazy, listy.</p>',
        ]);

        Page::updateOrCreate(['slug' => 'demo-wydarzenie'], [
            'title' => 'Demo: wydarzenie',
            'type' => 'event',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Szkolenie z zakresu współpracy międzysektorowej dla organizacji członkowskich.</p>',
            'event_mode' => 'stationary',
            'event_when' => now()->addWeeks(3),
            'event_location' => 'Centrum Obywatelskie, ul. Reymonta 20, Kraków',
            'event_how_to_join' => 'Zapisy przez formularz zgłoszeniowy poniżej.',
        ]);

        Page::updateOrCreate(['slug' => 'demo-harmonogram'], [
            'title' => 'Demo: harmonogram zajęć',
            'type' => 'schedule',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Przykładowy harmonogram cyklicznych spotkań konsultacyjnych.</p>',
            'schedule_items' => [
                ['date' => now()->addDays(7)->toDateString(), 'time' => '10:00', 'title' => 'Konsultacje dla nowych organizacji'],
                ['date' => now()->addDays(14)->toDateString(), 'time' => '10:00', 'title' => 'Konsultacje dla nowych organizacji'],
            ],
        ]);

        $aboutPage = Page::updateOrCreate(['slug' => 'demo-o-organizacji'], [
            'title' => 'Demo: O organizacji',
            'type' => 'about',
            'is_published' => true,
            'show_in_menu' => false,
            'about_intro' => 'Krakowskie Forum Organizacji Społecznych działa nieprzerwanie od 1998 roku na rzecz rozwoju społeczeństwa obywatelskiego.',
            'about_motto' => 'Razem dla lepszego jutra.',
        ]);

        // Zespół — podstrony typu "about_person", dzieci strony "O organizacji"
        // (sekcja "team" na tej stronie).
        foreach ([
            ['title' => 'Anna Nowak', 'person_role' => 'Prezeska Zarządu'],
            ['title' => 'Piotr Wiśniewski', 'person_role' => 'Koordynator ds. współpracy'],
            ['title' => 'Katarzyna Zielińska', 'person_role' => 'Specjalistka ds. projektów'],
        ] as $i => $member) {
            Page::updateOrCreate(
                ['slug' => 'demo-zespol-'.\Illuminate\Support\Str::slug($member['title'])],
                $member + [
                    'parent_id' => $aboutPage->id,
                    'type' => 'about_person',
                    'is_published' => true,
                    'show_in_menu' => false,
                    'order' => $i + 1,
                ]
            );
        }

        Page::updateOrCreate(['slug' => 'demo-instytucja-szkoleniowa'], [
            'title' => 'Demo: instytucja szkoleniowa',
            'type' => 'training_institution',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Wpis w Rejestrze Instytucji Szkoleniowych — dane wymagane ustawowo.</p>',
            'training_manager_name' => 'Jan Kowalski',
            'training_manager_title' => 'Kierownik ds. szkoleń',
            'training_ris_number' => '2.12/00000/2026',
            'training_bur_number' => 'BUR-000000',
        ]);

        Page::updateOrCreate(['slug' => 'demo-przeniesiono-do-bip'], [
            'title' => 'Demo: przeniesiono do BIP',
            'type' => 'bip_move',
            'is_published' => true,
            'show_in_menu' => false,
            'bip_move_url' => '/bip',
            'bip_move_note' => 'Ta informacja została przeniesiona do Biuletynu Informacji Publicznej.',
        ]);

        Page::updateOrCreate(['slug' => 'demo-strona-wewnetrzna'], [
            'title' => 'Demo: strona wewnętrzna',
            'type' => 'internal',
            'access_mode' => 'password',
            'access_password' => Hash::make('demo1234'),
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Treść widoczna wyłącznie po podaniu hasła (demo: <code>demo1234</code>).</p>',
        ]);

        Page::updateOrCreate(['slug' => 'demo-siatka-kafelkow'], [
            'title' => 'Demo: siatka kafelków',
            'type' => 'links_hub',
            'is_published' => true,
            'show_in_menu' => false,
            'hub_intro' => 'Szybki dostęp do najważniejszych zasobów federacji.',
            'hub_links' => [
                ['label' => 'Organizacje członkowskie', 'url' => '/organizacje-czlonkowskie'],
                ['label' => 'Dokumenty do pobrania', 'url' => '/dokumenty-do-pobrania'],
                ['label' => 'FAQ', 'url' => '/najczesciej-zadawane-pytania'],
            ],
        ]);

        Page::updateOrCreate(['slug' => 'demo-kafelki-ikony'], [
            'title' => 'Demo: kafelki (ikony + linki)',
            'type' => 'tiles_grid',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Dowolny układ kafelków z ikoną i linkiem.</p>',
            'tiles' => [
                ['label' => 'Projekty', 'url' => '/projekty', 'icon' => 'fa-solid fa-diagram-project'],
                ['label' => 'Kontakt', 'url' => '/kontakt', 'icon' => 'fa-solid fa-envelope'],
            ],
        ]);

        Page::updateOrCreate(['slug' => 'demo-wspolpraca'], [
            'title' => 'Demo: współpraca',
            'type' => 'wspolpraca',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Zapraszamy firmy i instytucje do współpracy z federacją.</p>',
            'cooperation_data' => [
                'form_enabled' => false,
            ],
        ]);

        Page::updateOrCreate(['slug' => 'demo-jak-bylo'], [
            'title' => 'Demo: prezentacja tego, co było',
            'type' => 'legacy',
            'is_published' => true,
            'show_in_menu' => false,
            'legacy_name' => 'Stara strona KraFOS',
            'legacy_intro' => 'Archiwalna prezentacja poprzedniej wersji serwisu.',
        ]);

        Page::updateOrCreate(['slug' => 'demo-marka'], [
            'title' => 'Demo: marka — identyfikacja wizualna',
            'type' => 'brand_assets',
            'access_mode' => 'password',
            'is_published' => true,
            'show_in_menu' => false,
            'content' => '<p>Pliki do pobrania: logotypy, szablony, księga znaku. Dostęp przez indywidualny login (patrz panel: Strony → Dostęp).</p>',
        ]);
    }
}
