<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów podstron działania „Wsparcie technologiczne dla NGO”: zakres, współpraca, koszty, kontakt i pytania.
 * Fakty bez zmian; usunięte myślniki, deklaracje („Wierzymy w przejrzystość…”), powiedzenia i zbędne zdania.
 *
 * Podmienia pojedyncze zdania (dokładne dopasowanie) w blokach lub w treści strony, więc jest idempotentny
 * i nie nadpisze fragmentów poprawionych później w panelu. Po zmianie bloków odświeża `content`.
 *
 *   php artisan db:seed --class=NgoSupportTextsHumanizedSeeder --force
 */
class NgoSupportTextsHumanizedSeeder extends Seeder
{
    /** @return array<string, array<string, string>> slug podstrony => [stary tekst => nowy tekst] */
    private function replacements(): array
    {
        return [
            'wsparcie-ngo-kontakt' => [
                'Masz pytanie o wsparcie technologiczne dla NGO, zanim wypełnisz zgłoszenie? Napisz albo zadzwoń — chętnie doradzimy, od czego zacząć.' => 'Masz pytanie o wsparcie technologiczne dla NGO przed wypełnieniem zgłoszenia? Napisz albo zadzwoń, chętnie doradzimy, od czego zacząć.',
            ],
            'wsparcie-ngo-ile-to-kosztuje' => [
                'Wierzymy w przejrzystość relacji. Dlatego nasze wsparcie techniczne opiera się na trzech zasadach:' => 'Nasze wsparcie techniczne opiera się na trzech zasadach:',
                'Żadnych ukrytych opłat, żadnych dodatkowych wydatków.' => 'Bez ukrytych opłat i dodatkowych wydatków.',
                'Wszystko kalkulujemy indywidualnie — zależnie od potrzeb Waszej organizacji: liczby osób i stanowisk, systemów, z których korzystacie, oraz zakresu wsparcia. Wycenę przygotowujemy po rozmowie i diagnozie, zanim podpiszemy umowę.' => 'Wycenę ustalamy indywidualnie, na podstawie liczby osób i stanowisk, systemów, z których korzystacie, i zakresu wsparcia. Przygotowujemy ją po rozmowie i diagnozie, przed podpisaniem umowy.',
                'ziemowitgil.pl</a> — <strong>Ziemowit Gil</strong>, tyfloinformatyk, edukator i prezes Fundacji FEER. Od ponad siedmiu lat łączy nowoczesne technologie z sektorem pozarządowym i edukacją, bo wierzy, że dobrze zaprojektowane narzędzie otwiera drzwi, których wcześniej nie było.' => 'ziemowitgil.pl</a>, czyli <strong>Ziemowit Gil</strong>, tyfloinformatyk, edukator i prezes Fundacji FEER. Od ponad siedmiu lat łączy nowoczesne technologie z sektorem pozarządowym i edukacją.',
                'Na co dzień zajmuje się dostępnością cyfrową, automatyzacją procesów i uczeniem technologii dzieci oraz młodzieży z niepełnosprawnościami. Organizacji nie zostawia samej z wiedzą — działa razem z nią.' => 'Na co dzień zajmuje się dostępnością cyfrową, automatyzacją procesów i nauką technologii dla dzieci i młodzieży z niepełnosprawnościami. Z organizacją pracuje razem.',
            ],
            'wsparcie-ngo-zakres-wsparcia' => [
                'Wsparcie dobieramy do Waszych potrzeb — możecie zacząć od jednego obszaru.' => 'Wsparcie dobieramy do Waszych potrzeb i możecie zacząć od jednego obszaru.',
                'porządek w treściach — albo nowa strona' => 'porządek w treściach albo nowa strona',
                'obieg dokumentów — mniej przepisywania i pomyłek.' => 'obieg dokumentów. Dzięki temu jest mniej przepisywania i pomyłek.',
                'Opisz go w formularzu — wybierz <strong>„Inne”</strong>.' => 'Opisz go w formularzu i wybierz <strong>„Inne”</strong>.',
            ],
            'wsparcie-ngo-jak-wyglada-wspolpraca' => [
                'Wypełniasz formularz zgłoszeniowy — to kilka minut. Numer KRS pozwoli nam od razu uzupełnić dane organizacji.' => 'Wypełniasz formularz zgłoszeniowy, to kilka minut. Dzięki numerowi KRS od razu uzupełniamy dane organizacji.',
            ],
            'wsparcie-ngo-najczestsze-pytania' => [
                'Nie. Wystarczy, że opiszesz, co nie działa albo co chcecie zmienić — własnymi słowami. Resztę dopytamy w rozmowie.' => 'Nie. Opisz własnymi słowami, co nie działa albo co chcecie zmienić. Resztę dopytamy w rozmowie.',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->replacements() as $slug => $map) {
            $page = Page::withoutGlobalScopes()->where('slug', $slug)->first();

            if (! $page) {
                $this->command?->warn("Pominięto {$slug}: brak podstrony.");

                continue;
            }

            $changed = false;

            if (is_array($page->blocks) && $page->blocks !== []) {
                $blocks = $this->replaceRecursive($page->blocks, $map, $changed);
                if ($changed) {
                    $blocks = BlockRenderer::normalize($blocks);
                    $page->blocks = $blocks;
                    $page->content = BlockRenderer::render($blocks);
                }
            } else {
                $content = (string) $page->content;
                $new = strtr($content, $map);
                if ($new !== $content) {
                    $page->content = $new;
                    $changed = true;
                }
            }

            if ($changed) {
                $page->save();
                $this->command?->info("Zmieniono podstronę {$slug}.");
            } else {
                $this->command?->line("Pominięto {$slug}: teksty były już zmieniane.");
            }
        }
    }

    /** @param  array<string, string>  $map */
    private function replaceRecursive(mixed $value, array $map, bool &$changed): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->replaceRecursive($v, $map, $changed);
            }

            return $value;
        }

        if (is_string($value)) {
            $new = strtr($value, $map);
            if ($new !== $value) {
                $changed = true;
            }

            return $new;
        }

        return $value;
    }
}
