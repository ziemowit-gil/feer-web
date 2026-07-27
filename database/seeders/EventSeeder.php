<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Przykładowe nadchodzące szkolenia i wydarzenia. Idempotentny — kluczujemy
     * po slugu, więc można uruchamiać wielokrotnie bez duplikatów. Terminy
     * liczone względem „teraz”, aby wpisy zawsze były nadchodzące.
     */
    public function run(): void
    {
        $events = [
            [
                'slug' => 'wcag-dla-ngo',
                'title' => 'Szkolenie z dostępności cyfrowej (WCAG) dla NGO',
                'lead' => 'Praktyczne wprowadzenie do WCAG 2.2 dla osób tworzących strony i dokumenty w organizacjach.',
                'description' => "Program:\n- podstawy WCAG 2.2 i wymogi ustawy o dostępności cyfrowej\n- kontrast, nawigacja klawiaturą, alternatywy tekstowe\n- dostępne dokumenty i PDF\n\nZapewniamy materiały i zaświadczenie.",
                'facilitator_name' => 'Anna Kowalska',
                'facilitator_role' => 'trenerka dostępności cyfrowej',
                'facilitator_bio' => "Od ponad 10 lat audytuje serwisy pod kątem WCAG i szkoli zespoły w instytucjach publicznych oraz NGO.\nProwadzi zajęcia w sposób praktyczny — na realnych przykładach uczestników.",
                'type' => 'szkolenie',
                'mode' => 'stacjonarnie',
                'location' => 'Nowy Sącz, ul. Barbackiego 28',
                'starts_at' => now()->addDays(9)->setTime(10, 0),
                'ends_at' => now()->addDays(9)->setTime(15, 0),
                'registration_url' => 'https://example.com/zapisy-wcag',
                'price_info' => 'Bezpłatne',
                'audience' => 'brand',
            ],
            [
                'slug' => 'warsztaty-canva',
                'title' => 'Warsztaty: grafiki w Canva dla wolontariuszy',
                'lead' => 'Zrób samodzielnie plakat i grafikę do social mediów — od zera, bez doświadczenia.',
                'description' => "Na warsztacie krok po kroku przygotujesz komplet grafik dla swojej akcji.\n\nWeź własny laptop. Konto w Canva założymy na miejscu.",
                'facilitator_name' => 'Marta Nowak',
                'facilitator_role' => 'projektantka i koordynatorka komunikacji',
                'facilitator_bio' => 'Na co dzień odpowiada za materiały wizualne fundacji. Pokaże proste triki, dzięki którym grafiki wyglądają profesjonalnie.',
                'type' => 'warsztat',
                'mode' => 'hybrydowo',
                'location' => 'Nowy Sącz + online',
                'online_url' => 'https://meet.example.com/canva',
                'starts_at' => now()->addDays(16)->setTime(17, 0),
                'ends_at' => now()->addDays(16)->setTime(19, 0),
                'contact_email' => 'szkolenia@feer.org.pl',
                'price_info' => '20 zł',
                'audience' => 'ngo',
            ],
            [
                'slug' => 'webinar-fundusze',
                'title' => 'Webinar: skąd wziąć fundusze na projekt społeczny',
                'lead' => 'Przegląd źródeł finansowania i praktyczne wskazówki do wniosku.',
                'description' => "Omówimy granty, dotacje, zbiórki i sponsoring.\n\nNa koniec sesja pytań i odpowiedzi.",
                'facilitator_name' => 'Katarzyna Wiśniewska',
                'facilitator_role' => 'specjalistka ds. fundraisingu',
                'facilitator_bio' => 'Pozyskała finansowanie dla kilkudziesięciu projektów społecznych. Podpowie, od czego zacząć i jak uniknąć typowych błędów.',
                'type' => 'webinar',
                'mode' => 'zdalnie',
                'online_url' => 'https://meet.example.com/fundusze',
                'starts_at' => now()->addDays(23)->setTime(18, 0),
                'registration_url' => 'https://example.com/webinar-fundusze',
                'price_info' => 'Bezpłatne',
                'audience' => 'brand',
            ],
        ];

        foreach ($events as $data) {
            Event::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['is_published' => true, 'registration_cta_label' => 'Zapisz się']),
            );
        }
    }
}
