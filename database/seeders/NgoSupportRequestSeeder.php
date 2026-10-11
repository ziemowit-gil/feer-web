<?php

namespace Database\Seeders;

use App\Models\NgoSupportRequest;
use Illuminate\Database\Seeder;

/**
 * 10 przykładowych zgłoszeń „Wsparcie technologiczne dla NGO” z różnymi statusami.
 *
 * Czysty Eloquent ze stałymi danymi — bez Fakera, bo na produkcji Composer instaluje
 * zależności z --no-dev (fakerphp/faker jest tylko w require-dev). Losowe dane do testów
 * daje NgoSupportRequestFactory.
 *
 * Uruchom: php artisan db:seed --class=NgoSupportRequestSeeder (na serwerze: php84 artisan …).
 * Powtarzalny: aktualizuje istniejące przykładowe zgłoszenia po adresie e-mail zamiast je mnożyć.
 */
class NgoSupportRequestSeeder extends Seeder
{
    public const REQUESTS = [
        [
            'name' => 'Joanna Wiśniewska', 'organization_name' => 'Fundacja Otwarte Drzwi', 'role' => 'Zarząd',
            'email' => 'joanna.wisniewska@otwartedrzwi.org.pl', 'phone' => '601 234 567', 'website' => 'https://otwartedrzwi.org.pl',
            'support_areas' => ['M365/Google Workspace', 'Bezpieczeństwo i kopie zapasowe'],
            'description' => 'Mamy 12 osób w zespole i każdy korzysta z prywatnej skrzynki Gmail. Chcemy przejść na Microsoft 365 dla organizacji non-profit, ale nie wiemy, jak przenieść pocztę i dokumenty bez utraty danych.',
            'preferred_contact_channel' => 'online_meeting', 'preferred_time' => 'Wtorki i czwartki po 15:00', 'status' => 'new', 'days_ago' => 1,
        ],
        [
            'name' => 'Tomasz Kaczmarek', 'organization_name' => 'Stowarzyszenie Razem w Lublinie', 'role' => 'Koordynator projektu',
            'email' => 'tomasz.kaczmarek@razemwlublinie.pl', 'phone' => null, 'website' => 'https://razemwlublinie.pl',
            'support_areas' => ['Strona WWW / CMS', 'Dostępność cyfrowa (WCAG)'],
            'description' => 'Nasza strona na WordPressie nie była aktualizowana od 2021 roku, a hosting zgłasza zagrożenia. Nikt w zespole nie wie, jak zalogować się do panelu — osoba, która ją robiła, już z nami nie współpracuje.',
            'preferred_contact_channel' => 'email', 'preferred_time' => null, 'status' => 'new', 'days_ago' => 2,
        ],
        [
            'name' => 'Agnieszka Zielińska', 'organization_name' => 'Fundacja Wsparcia Seniorów „Złota Jesień”', 'role' => 'Pracownik',
            'email' => 'agnieszka.zielinska@zlotajesien.pl', 'phone' => '512 908 114', 'website' => 'https://zlotajesien.pl',
            'support_areas' => ['Bezpieczeństwo i kopie zapasowe'],
            'description' => 'Po ataku phishingowym ktoś przejął skrzynkę prezeski i rozesłał fałszywe prośby o przelew. Potrzebujemy pomocy z włączeniem uwierzytelniania dwuskładnikowego i szkoleniem dla zespołu.',
            'preferred_contact_channel' => 'phone', 'preferred_time' => 'Dni robocze 9:00–12:00', 'status' => 'new', 'days_ago' => 3,
        ],
        [
            'name' => 'Paweł Nowicki', 'organization_name' => 'Fundacja Edukacji Cyfrowej „Bit po Bicie”', 'role' => 'Zarząd',
            'email' => 'pawel.nowicki@bitpobicie.org', 'phone' => '698 330 221', 'website' => 'https://bitpobicie.org',
            'support_areas' => ['Automatyzacja procesów', 'M365/Google Workspace'],
            'description' => 'Zapisy na warsztaty zbieramy przez formularz, a potem ręcznie przepisujemy je do Excela i wysyłamy potwierdzenia. Przy 200 uczestnikach miesięcznie to kilka dni pracy.',
            'preferred_contact_channel' => 'online_meeting', 'preferred_time' => 'Najlepiej w piątek przed południem', 'status' => 'in_progress', 'days_ago' => 9,
        ],
        [
            'name' => 'Katarzyna Lewandowska', 'organization_name' => 'Stowarzyszenie Przyjaciół Osób Niewidomych „Światło”', 'role' => 'Koordynator projektu',
            'email' => 'k.lewandowska@swiatlo.org.pl', 'phone' => '505 771 039', 'website' => 'https://swiatlo.org.pl',
            'support_areas' => ['Dostępność cyfrowa (WCAG)', 'Strona WWW / CMS'],
            'description' => 'Nasi podopieczni to w dużej części osoby słabowidzące. Chcemy, żeby newsletter i dokumenty PDF były czytelne dla czytników ekranu, a strona przeszła audyt WCAG 2.2.',
            'preferred_contact_channel' => 'email', 'preferred_time' => 'Dowolnie, prosimy o wcześniejszy kontakt mailowy', 'status' => 'in_progress', 'days_ago' => 14,
        ],
        [
            'name' => 'Michał Wójcik', 'organization_name' => 'Fundacja Pomocy Zwierzętom „Łapa”', 'role' => 'Wolontariusz',
            'email' => 'michal.wojcik@fundacjalapa.pl', 'phone' => '733 482 900', 'website' => 'https://facebook.com/fundacjalapa',
            'support_areas' => ['Serwery i hosting', 'Strona WWW / CMS'],
            'description' => 'Płacimy za hosting trzy razy więcej niż potrzebujemy i nie wiemy, za co. Domena wygasa za miesiąc, a dostęp do panelu rejestratora ma były członek zarządu.',
            'preferred_contact_channel' => 'phone', 'preferred_time' => 'Po 17:00 — pracujemy jako wolontariusze', 'status' => 'contacted', 'days_ago' => 18,
        ],
        [
            'name' => 'Ewa Dąbrowska', 'organization_name' => 'Koło Gospodyń Wiejskich w Sękowej', 'role' => 'Inna',
            'email' => 'kgw.sekowa@wp.pl', 'phone' => '690 112 345', 'website' => null,
            'support_areas' => ['Inne', 'M365/Google Workspace'],
            'description' => 'Potrzebujemy pomocy w wyborze sprzętu (laptop, projektor) do świetlicy w ramach grantu — budżet to ok. 15 tys. zł — i w założeniu wspólnej skrzynki dla koła.',
            'preferred_contact_channel' => 'phone', 'preferred_time' => 'Wtorki i czwartki po 15:00', 'status' => 'contacted', 'days_ago' => 22,
        ],
        [
            'name' => 'Krzysztof Mazur', 'organization_name' => 'Stowarzyszenie Rodzin Zastępczych „Gniazdo”', 'role' => 'Zarząd',
            'email' => 'krzysztof.mazur@gniazdo-rodziny.pl', 'phone' => null, 'website' => 'https://gniazdo-rodziny.pl',
            'support_areas' => ['Bezpieczeństwo i kopie zapasowe', 'Serwery i hosting'],
            'description' => 'Dane podopiecznych (w tym dane wrażliwe) mieliśmy tylko na jednym laptopie w biurze. Wdrożyliśmy już szyfrowanie i automatyczne kopie zapasowe — zgłoszenie zamknięte po szkoleniu zespołu.',
            'preferred_contact_channel' => 'email', 'preferred_time' => null, 'status' => 'completed', 'days_ago' => 35,
        ],
        [
            'name' => 'Magdalena Kowalczyk', 'organization_name' => 'Fundacja Aktywnej Rehabilitacji „Krok Dalej”', 'role' => 'Pracownik',
            'email' => 'magdalena.kowalczyk@krokdalej.org.pl', 'phone' => '784 560 002', 'website' => 'https://krokdalej.org.pl',
            'support_areas' => ['Automatyzacja procesów'],
            'description' => 'Chcieliśmy, żeby darowizny z bramki płatności automatycznie trafiały do bazy darczyńców i żeby wysyłało się podziękowanie z potwierdzeniem. Integracja działa od miesiąca.',
            'preferred_contact_channel' => 'online_meeting', 'preferred_time' => 'Dni robocze 9:00–12:00', 'status' => 'completed', 'days_ago' => 41,
        ],
        [
            'name' => 'Andrzej Pawlak', 'organization_name' => 'Uniwersytet Trzeciego Wieku w Sanoku', 'role' => 'Wolontariusz',
            'email' => 'andrzej.pawlak@utw-sanok.pl', 'phone' => '601 998 776', 'website' => 'https://utw-sanok.pl',
            'support_areas' => ['Inne'],
            'description' => 'Pytaliśmy o system zgłoszeń dla wolontariuszy. Po rozmowie okazało się, że wystarczy formularz na stronie — sprawa zarchiwizowana.',
            'preferred_contact_channel' => 'phone', 'preferred_time' => 'Najlepiej w piątek przed południem', 'status' => 'archived', 'days_ago' => 58,
        ],
    ];

    public function run(): void
    {
        foreach (self::REQUESTS as $row) {
            $created = now()->subDays($row['days_ago'])->setTime(10, 0)->addMinutes(crc32($row['email']) % 420);
            unset($row['days_ago']);

            $request = NgoSupportRequest::updateOrCreate(
                ['email' => $row['email']],
                $row + ['privacy_consent' => true],
            );
            $request->timestamps = false;
            $request->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
        }

        $this->command?->info('Przykładowe zgłoszenia wsparcia technologicznego NGO: '.count(self::REQUESTS).'.');
    }
}
