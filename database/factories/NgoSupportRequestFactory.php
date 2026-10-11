<?php

namespace Database\Factories;

use App\Models\NgoSupportRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Realistyczne zgłoszenia NGO po polsku: nazwy organizacji i opisy problemów dobrane do obszarów wsparcia.
 *
 * @extends Factory<NgoSupportRequest>
 */
class NgoSupportRequestFactory extends Factory
{
    protected $model = NgoSupportRequest::class;

    /** Nazwy organizacji: [nazwa, domena]. */
    public const ORGANIZATIONS = [
        ['Fundacja Otwarte Drzwi', 'otwartedrzwi.org.pl'],
        ['Stowarzyszenie Razem w Lublinie', 'razemwlublinie.pl'],
        ['Fundacja Zielony Kwadrat', 'zielonykwadrat.org'],
        ['Stowarzyszenie Przyjaciół Osób Niewidomych „Światło”', 'swiatlo.org.pl'],
        ['Fundacja Wsparcia Seniorów „Złota Jesień”', 'zlotajesien.pl'],
        ['Koło Gospodyń Wiejskich w Sękowej', null],
        ['Fundacja Edukacji Cyfrowej „Bit po Bicie”', 'bitpobicie.org'],
        ['Stowarzyszenie Rodzin Zastępczych „Gniazdo”', 'gniazdo-rodziny.pl'],
        ['Fundacja Pomocy Zwierzętom „Łapa”', 'fundacjalapa.pl'],
        ['Stowarzyszenie Kulturalne „Scena Podgórze”', 'scenapodgorze.pl'],
        ['Fundacja Aktywnej Rehabilitacji „Krok Dalej”', 'krokdalej.org.pl'],
        ['Uniwersytet Trzeciego Wieku w Sanoku', 'utw-sanok.pl'],
    ];

    /** Opisy wyzwań wg obszaru wsparcia. */
    public const DESCRIPTIONS = [
        'M365/Google Workspace' => [
            'Mamy 12 osób w zespole i każdy korzysta z prywatnej skrzynki Gmail. Chcemy przejść na Microsoft 365 dla organizacji non-profit, ale nie wiemy, jak przenieść pocztę i dokumenty bez utraty danych.',
            'Pliki projektowe trzymamy na pendrive’ach i w załącznikach maili. Potrzebujemy wspólnego dysku z uprawnieniami dla wolontariuszy, którzy zmieniają się co kilka miesięcy.',
        ],
        'Strona WWW / CMS' => [
            'Nasza strona na WordPressie nie była aktualizowana od 2021 roku, a hosting zgłasza zagrożenia. Nikt w zespole nie wie, jak zalogować się do panelu — osoba, która ją robiła, już z nami nie współpracuje.',
            'Chcemy nowej, prostej strony, którą sami będziemy aktualizować: aktualności, wydarzenia i zbiórka 1,5%. Obecna jest na kreatorze i trudno w niej cokolwiek zmienić.',
        ],
        'Bezpieczeństwo i kopie zapasowe' => [
            'Po ataku phishingowym ktoś przejął skrzynkę prezeski i rozesłał fałszywe prośby o przelew. Potrzebujemy pomocy z włączeniem uwierzytelniania dwuskładnikowego i szkoleniem dla zespołu.',
            'Dane podopiecznych (w tym dane wrażliwe) mamy tylko na jednym laptopie w biurze. Nie robimy kopii zapasowych i obawiamy się kontroli RODO.',
        ],
        'Serwery i hosting' => [
            'Płacimy za hosting trzy razy więcej niż potrzebujemy i nie wiemy, za co. Domena wygasa za miesiąc, a dostęp do panelu rejestratora ma były członek zarządu.',
            'Mamy w biurze stary serwer NAS, który głośno pracuje i coraz częściej się wyłącza. Chcemy przenieść dane do chmury w rozsądnej cenie.',
        ],
        'Automatyzacja procesów' => [
            'Zapisy na warsztaty zbieramy przez formularz, a potem ręcznie przepisujemy je do Excela i wysyłamy potwierdzenia. Przy 200 uczestnikach miesięcznie to kilka dni pracy.',
            'Chcielibyśmy, żeby darowizny z bramki płatności automatycznie trafiały do naszej bazy darczyńców i żeby wysyłało się podziękowanie z potwierdzeniem.',
        ],
        'Dostępność cyfrowa (WCAG)' => [
            'Realizujemy projekt z funduszy unijnych i musimy mieć dostępną stronę oraz deklarację dostępności. Nie wiemy, od czego zacząć audyt.',
            'Nasi podopieczni to w dużej części osoby słabowidzące. Chcemy, żeby newsletter i dokumenty PDF były czytelne dla czytników ekranu.',
        ],
        'Inne' => [
            'Potrzebujemy pomocy w wyborze sprzętu (laptopy, projektor) do świetlicy dla dzieci w ramach grantu — budżet to ok. 15 tys. zł.',
            'Chcemy uruchomić prosty system zgłoszeń dla wolontariuszy, ale nie wiemy, czy wystarczy formularz, czy potrzebujemy osobnej aplikacji.',
        ],
    ];

    public const PREFERRED_TIMES = [
        'Wtorki i czwartki po 15:00',
        'Dni robocze 9:00–12:00',
        'Najlepiej w piątek przed południem',
        'Po 17:00 — pracujemy jako wolontariusze',
        'Dowolnie, prosimy o wcześniejszy kontakt mailowy',
    ];

    public function definition(): array
    {
        $pl = fake('pl_PL');
        [$organization, $domain] = $this->faker->randomElement(self::ORGANIZATIONS);

        // 1–3 obszary; „Inne” rzadziej i nigdy jako jedyny dodatek do wielu.
        $areas = $this->faker->randomElements(
            array_values(array_diff(NgoSupportRequest::SUPPORT_AREAS, ['Inne'])),
            $this->faker->numberBetween(1, 3),
        );
        if ($this->faker->boolean(15)) {
            $areas[] = 'Inne';
        }

        // Imię i nazwisko tej samej płci (polskie nazwiska odmieniają się: Wysocki / Wysocka).
        $female = $this->faker->boolean();
        $first = $female ? $pl->firstNameFemale() : $pl->firstNameMale();
        $last = $female ? $pl->lastNameFemale() : $pl->lastNameMale();
        $emailDomain = $domain ?? $this->faker->randomElement(['gmail.com', 'wp.pl', 'o2.pl', 'interia.pl']);
        $email = Str::slug($first).'.'.Str::slug($last).'@'.$emailDomain;

        $channel = $this->faker->randomElement(array_keys(NgoSupportRequest::CONTACT_CHANNELS));

        return [
            'name'                      => "{$first} {$last}",
            'organization_name'         => $organization,
            'role'                      => $this->faker->randomElement(NgoSupportRequest::ROLES),
            'email'                     => $email,
            'phone'                     => $channel === 'phone' || $this->faker->boolean(50) ? $pl->numerify('### ### ###') : null,
            'website'                   => $domain ? 'https://'.$domain : ($this->faker->boolean(50) ? 'https://facebook.com/'.Str::slug($organization) : null),
            'support_areas'             => $areas,
            'description'               => $this->faker->randomElement(self::DESCRIPTIONS[$areas[0]]),
            'preferred_contact_channel' => $channel,
            'preferred_time'            => $channel === 'email' && $this->faker->boolean(50) ? null : $this->faker->randomElement(self::PREFERRED_TIMES),
            'status'                    => 'new',
            'privacy_consent'           => true,
            'created_at'                => $created = $this->faker->dateTimeBetween('-60 days', 'now'),
            'updated_at'                => $created,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
