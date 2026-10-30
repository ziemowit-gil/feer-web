<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Redakcja pola „Dlaczego to robimy” (`why`) w działaniach oraz dwóch dodatkowych tekstów: sekcji „Współpraca tylko
 * w ramach partnerstw” w „Dostępności w praktyce” i opisu meta „Wsparcia technologicznego dla NGO”.
 * Fakty bez zmian; usunięte kontrasty „nie X, lecz Y”, hasła i myślniki.
 *
 * Idempotentny: pole jest podmieniane tylko wtedy, gdy ma dokładnie pierwotne brzmienie, więc seeder nie nadpisze
 * zmian zrobionych później w panelu.
 *
 *   php artisan db:seed --class=ProjectWhyTextsHumanizedSeeder --force
 */
class ProjectWhyTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $n = "\r\n";

        $why = [
            'akademia-dostepnosci-w-ngo' => [
                "Wyobraź sobie, że organizujesz świetne szkolenie — merytoryczne, potrzebne, dobrze przygotowane. Ale osoba poruszająca się na wózku nie może wejść do budynku. Ktoś niedosłyszący nie rozumie prowadzącego, bo nie ma pętli indukcyjnej. Uczestnik z dysleksją gubi się w materiałach, bo tekst jest napisany zbyt skomplikowanym językiem. Formalnie wydarzenie się odbyło. W praktyce — dla części osób po prostu nie istniało.{$n} Dostępność to nie przepis do odhaczenia w formularzu. To decyzja, że Twoje działania są naprawdę dla wszystkich — bez względu na to, jak ktoś się porusza, jak widzi, jak słyszy, jak przetwarza informacje. W Polsce żyje kilka milionów osób ze szczególnymi potrzebami. Wśród mieszkańców Krakowa, którym służy Twoja organizacja, są seniorzy, osoby z niepełnosprawnościami, rodzice z wózkami, osoby z czasowymi ograniczeniami sprawności. Wszyscy mają prawo korzystać z tego, co oferujesz.{$n} A poza tym — dostępność po prostu popłaca. Oferty, które opisują ją konkretnie i rzetelnie, dostają wyższe oceny. Organizacje, które ją wdrażają, docierają do szerszego grona odbiorców. Organizacje, które traktują ją poważnie, budują zaufanie i reputację, które procentują latami.",
                "Organizujesz dobre, merytoryczne szkolenie. Osoba poruszająca się na wózku nie może wejść do budynku. Ktoś niedosłyszący nie rozumie prowadzącego, bo nie ma pętli indukcyjnej. Uczestnik z dysleksją gubi się w materiałach napisanych zbyt skomplikowanym językiem. Formalnie wydarzenie się odbyło, ale dla części osób go nie było.{$n}{$n}Dostępność oznacza, że Twoje działania są dla wszystkich, niezależnie od tego, jak ktoś się porusza, widzi, słyszy i przetwarza informacje. W Polsce żyje kilka milionów osób ze szczególnymi potrzebami. Wśród mieszkańców Krakowa, którym służy Twoja organizacja, są seniorzy, osoby z niepełnosprawnościami, rodzice z wózkami i osoby z czasowymi ograniczeniami sprawności. Wszyscy mają prawo korzystać z tego, co oferujesz.{$n}{$n}Dostępność się też opłaca. Oferty, które opisują ją konkretnie i rzetelnie, dostają wyższe oceny. Organizacje, które ją wdrażają, docierają do szerszego grona odbiorców, a te, które traktują ją poważnie, budują zaufanie i reputację na lata.",
            ],
            'materialy-edukacyjne' => [
                "Wierzymy, że każda zmiana zaczyna się od edukacji. Świat staje się bardziej otwarty wtedy, gdy uczymy się rozumieć siebie nawzajem – już od najmłodszych lat.{$n}{$n}Wiemy, że stereotypy i uprzedzenia najczęściej biorą się z niewiedzy. Dlatego chcemy dawać dzieciom, młodzieży i dorosłym prostą, dobrą wiedzę – taką, która uczy szacunku, ciekawości i akceptacji.{$n}{$n}Marzy nam się świat, w którym osoby z niepełnosprawnościami są traktowane naturalnie – bez litości, bez strachu, bez dystansu. Świat, w którym różnorodność nie dzieli, tylko łączy.{$n}{$n}Dlatego działamy. Krok po kroku, lekcja po lekcji, rozmowa po rozmowie – budujemy razem bardziej otwarte społeczeństwo.",
                "Każda zmiana zaczyna się od edukacji. Świat staje się bardziej otwarty, gdy od najmłodszych lat uczymy się rozumieć siebie nawzajem.{$n}{$n}Stereotypy i uprzedzenia najczęściej biorą się z niewiedzy. Dlatego chcemy dawać dzieciom, młodzieży i dorosłym prostą wiedzę, która uczy szacunku, ciekawości i akceptacji.{$n}{$n}Chcemy, żeby osoby z niepełnosprawnościami były traktowane naturalnie, bez litości, strachu i dystansu, a różnorodność łączyła ludzi.{$n}{$n}Budujemy bardziej otwarte społeczeństwo krok po kroku: lekcja po lekcji i rozmowa po rozmowie.",
            ],
            'szkolenia-dla-wolontariuszy' => [
                'Organizacje pozarządowe zmieniają świat — ale zbyt często tracą czas i energię na rzeczy, które dobrze dobrane narzędzia mogłyby wziąć na siebie. Wierzymy, że wolontariusz wyposażony w cyfrowe kompetencje robi więcej, działa pewniej i czerpie większą satysfakcję z pracy. Dlatego stworzyliśmy program, który nie uczy teorii — uczy działania. Bo każda godzina odzyskana z biurokratycznego chaosu to godzina, którą można oddać temu, co naprawdę ważne.',
                'Organizacje pozarządowe zmieniają świat, ale zbyt często tracą czas i energię na rzeczy, które mogłyby wziąć na siebie dobrze dobrane narzędzia. Wolontariusz z cyfrowymi kompetencjami robi więcej, działa pewniej i czerpie większą satysfakcję z pracy. Dlatego stworzyliśmy program, który uczy działania, a nie teorii. Każda godzina odzyskana z biurokratycznego chaosu to godzina dla tego, co naprawdę ważne.',
            ],
            'wsparcie-ngo' => [
                "Małe fundacje i stowarzyszenia robią gigantyczną pracę. Zmieniają świat, często z ograniczonymi zasobami. Czasem jednak od tej pracy Was odciąga codzienna walka z technologią — konfiguracja serwerów, bezpieczeństwo danych, narzędzia do współpracy w zespole.{$n}{$n}To nie powinno być przeszkodą. Pasja i chęć pomagania powinny być w centrum Waszej organizacji, a nie ostateczna pozycja na liście zmartwień.{$n}{$n}Dlatego jesteśmy tutaj — by zdjąć ten ciężar z Waszych barków. Zapewniamy wsparcie techniczne, które sprawia, że technologia służy Waszej misji, a nie odwrotnie.",
                "Małe fundacje i stowarzyszenia wykonują ogromną pracę, często z ograniczonymi zasobami. Czasem odciąga je od niej codzienna walka z technologią: konfiguracja serwerów, bezpieczeństwo danych, narzędzia do współpracy w zespole.{$n}{$n}W centrum Waszej organizacji powinny być pasja i chęć pomagania, a technologia nie powinna stawać na przeszkodzie.{$n}{$n}Jesteśmy po to, żeby zdjąć ten ciężar z Waszych barków. Zapewniamy wsparcie techniczne, dzięki któremu technologia służy Waszej misji.",
            ],
            'edukacja-w-social-media' => [
                'Wokół niepełnosprawności krąży wiele mitów — o tym, czego osoby z niepełnosprawnościami nie mogą, nie chcą albo nie potrzebują. Te mity mają realne konsekwencje: kształtują decyzje projektantów, pracodawców, twórców stron i urzędników. Najskuteczniejszym miejscem, żeby je obalać, jest właśnie tam, gdzie te przekonania się formują i powielają — czyli w mediach społecznościowych. Nie czekamy, aż ktoś trafi na szkolenie. Wychodzimy do ludzi.',
                'Wokół niepełnosprawności krąży wiele mitów o tym, czego osoby z niepełnosprawnościami nie mogą, nie chcą albo nie potrzebują. Mity mają realne konsekwencje: kształtują decyzje projektantów, pracodawców, twórców stron i urzędników. Najlepiej obalać je tam, gdzie takie przekonania się formują i powielają, czyli w mediach społecznościowych. Wychodzimy do ludzi, zamiast czekać, aż ktoś trafi na szkolenie.',
            ],
            'dostepnosc-w-praktyce' => [
                'Dostępność wciąż jest traktowana jak obowiązek formalny, a nie realna wartość. Tymczasem najtrwalsze zmiany zachodzą wtedy, gdy organizacje przechodzą przez ten proces z kimś, kto rozumie zarówno przepisy, jak i ludzką perspektywę. My to rozumiemy — bo sami jesteśmy fundacją i sami wdrażamy to, o czym mówimy.',
                'Dostępność wciąż jest traktowana głównie jak obowiązek formalny. Tymczasem najtrwalsze zmiany zachodzą wtedy, gdy organizacje przechodzą przez ten proces z kimś, kto rozumie zarówno przepisy, jak i ludzką perspektywę. My to rozumiemy, bo sami jesteśmy fundacją i sami wdrażamy to, o czym mówimy.',
            ],
        ];

        $partnership = [
            'Nie prowadzimy otwartego naboru — współpracę nawiązujemy bezpośrednio, na podstawie wzajemnego zaufania i dopasowania do misji fundacji. Jeśli chcesz nawiązać kontakt, napisz do nas.' => 'Nie prowadzimy otwartego naboru. Współpracę nawiązujemy bezpośrednio, na podstawie wzajemnego zaufania i dopasowania do misji fundacji. Jeśli chcesz się z nami skontaktować, napisz.',
        ];

        $metaOld = 'Wsparcie technologiczne dla NGO: Microsoft 365 i Google Workspace, strony WWW, bezpieczeństwo, hosting i dostępność WCAG — w stałym, miesięcznym abonamencie.';
        $metaNew = 'Wsparcie technologiczne dla NGO: Microsoft 365 i Google Workspace, strony WWW, bezpieczeństwo, hosting i dostępność WCAG, w stałym miesięcznym abonamencie.';

        $changed = 0;

        foreach (Project::withoutGlobalScopes()->whereIn('slug', array_merge(array_keys($why), ['dostepnosc-w-praktyce']))->get() as $project) {
            $dirty = false;

            [$old, $new] = $why[$project->slug];
            if ($project->why === $old) {
                $project->why = $new;
                $dirty = true;
            }

            if ($project->slug === 'dostepnosc-w-praktyce' && is_array($project->custom_sections)) {
                $sections = $project->custom_sections;
                foreach ($sections as $i => $section) {
                    $content = strtr((string) ($section['content'] ?? ''), $partnership);
                    if ($content !== ($section['content'] ?? '')) {
                        $sections[$i]['content'] = $content;
                        $dirty = true;
                    }
                }
                $project->custom_sections = $sections;
            }

            if ($project->slug === 'wsparcie-ngo' && $project->meta_description === $metaOld) {
                $project->meta_description = $metaNew;
                $dirty = true;
            }

            if ($dirty) {
                $project->save();
                $changed++;
                $this->command?->info("Zmieniono {$project->slug}.");
            }
        }

        if ($changed === 0) {
            $this->command?->line('Pominięto: teksty „Dlaczego to robimy” były już zmieniane.');
        }
    }
}
