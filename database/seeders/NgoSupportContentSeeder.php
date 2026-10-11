<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Treść działania „Wsparcie technologiczne dla NGO” (/dzialania/wsparcie-ngo): podstrony w zakładkach /
 * menu bocznym — „Zakres wsparcia”, „Jak wygląda współpraca”, „Najczęstsze pytania”.
 * Dodaje tylko brakujące podstrony (po adresie) — treści zmienionych w panelu nie nadpisuje. Ustawia też
 * kolejność: O projekcie → Zakres → Współpraca → Ile to kosztuje? → Pytania → Formularz (tylko podstronom
 * z kolejnością 0, czyli nieułożonym ręcznie).
 *
 *   php artisan db:seed --class=NgoSupportContentSeeder   (na serwerze: php84 artisan … --force)
 */
class NgoSupportContentSeeder extends Seeder
{
    public const PROJECT_SLUG = 'wsparcie-ngo';

    public function run(): void
    {
        $project = Project::where('slug', self::PROJECT_SLUG)->first();
        if (! $project) {
            $this->command?->warn('Brak działania /dzialania/'.self::PROJECT_SLUG.' — pominięto.');

            return;
        }

        foreach ($this->pages() as $slug => [$title, $order, $content]) {
            $page = Page::withTrashed()->where('slug', $slug)->first();
            if ($page) {
                $this->command?->line("Podstrona „{$page->title}” już istnieje — bez zmian.");

                continue;
            }
            $page = new Page;
            $page->forceFill([
                'site_id' => $project->site_id,
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'type' => 'standard',
                'project_id' => $project->id,
                'project_display' => 'tab',
                'is_published' => true,
                'order' => $order,
            ])->save();
            $this->command?->info("Dodano podstronę „{$title}”.");
        }

        // Kolejność istniejących podstron (tylko nieułożonych ręcznie).
        Page::where('project_id', $project->id)->where('order', 0)->get()->each(function (Page $p) {
            $order = match (true) {
                $p->type === 'form_page' => 90,
                str_contains($p->slug, 'ile-to-kosztuje') => 30,
                default => null,
            };
            if ($order !== null) {
                $p->forceFill(['order' => $order])->save();
            }
        });
    }

    /** @return array<string, array{0: string, 1: int, 2: string}> slug => [tytuł, kolejność, treść HTML] */
    private function pages(): array
    {
        return [
            'wsparcie-ngo-zakres-wsparcia' => ['Zakres wsparcia', 10, <<<'HTML'
<p>Pomagamy w tym, co w organizacji „musi działać”, żeby zespół mógł zająć się misją. Wsparcie dobieramy do Waszych potrzeb — możecie zacząć od jednego obszaru.</p>
<h3>Microsoft 365 i Google Workspace</h3>
<p>Konfiguracja poczty, kont i uprawnień, dyski współdzielone, przejście z prywatnych skrzynek na domenę organizacji, wdrożenie i wsparcie zespołu w codziennej pracy.</p>
<h3>Strona WWW i CMS</h3>
<p>Aktualizacje, poprawki, nowe podstrony i formularze, porządek w treściach, a także pomoc przy nowej stronie — tak, żeby dało się ją samodzielnie prowadzić.</p>
<h3>Bezpieczeństwo i kopie zapasowe</h3>
<p>Silne logowanie (dwuetapowa weryfikacja), menedżer haseł, regularne kopie zapasowe i sprawdzanie, czy da się z nich odtworzyć dane. Podstawy bezpieczeństwa dla całego zespołu.</p>
<h3>Serwery i hosting</h3>
<p>Wybór i konfiguracja hostingu, domeny i certyfikaty SSL, przeniesienie strony lub poczty, monitorowanie i reagowanie na awarie.</p>
<h3>Automatyzacja procesów</h3>
<p>Formularze, które same trafiają do arkusza lub CRM, przypomnienia, obieg dokumentów — mniej przepisywania, mniej pomyłek.</p>
<h3>Dostępność cyfrowa (WCAG)</h3>
<p>Sprawdzenie strony i dokumentów pod kątem dostępności, deklaracja dostępności, poprawki i szkolenie z przygotowywania dostępnych treści. To nasza specjalność — sami prowadzimy fundację dla osób z niepełnosprawnościami.</p>
<p>Nie widzisz swojego problemu na liście? Opisz go w formularzu zgłoszeniowym — wybierz „Inne”.</p>
HTML],
            'wsparcie-ngo-jak-wyglada-wspolpraca' => ['Jak wygląda współpraca', 20, <<<'HTML'
<ol>
<li><strong>Zgłoszenie</strong> — wypełniasz formularz zgłoszeniowy. Zajmie to kilka minut; numer KRS pozwoli nam od razu uzupełnić dane organizacji.</li>
<li><strong>Rozmowa</strong> — w ciągu 3 dni roboczych kontaktujemy się wybranym przez Ciebie kanałem i dopytujemy o szczegóły.</li>
<li><strong>Diagnoza i propozycja</strong> — sprawdzamy, co działa, a co wymaga zmian, i przygotowujemy propozycję zakresu wsparcia oraz miesięcznego abonamentu.</li>
<li><strong>Umowa</strong> — ustalamy zasady współpracy, w tym dostęp do systemów i ochronę danych osobowych.</li>
<li><strong>Stałe wsparcie</strong> — zgłaszasz potrzeby wtedy, kiedy się pojawiają, a my dbamy także o to, czego na co dzień nie widać: aktualizacje, kopie i bezpieczeństwo.</li>
</ol>
<p>Przez cały czas masz jedną osobę kontaktową, która zna Waszą organizację i jej systemy.</p>
HTML],
            'wsparcie-ngo-najczestsze-pytania' => ['Najczęstsze pytania', 40, <<<'HTML'
<h3>Czy muszę znać się na technologii?</h3>
<p>Nie. Wystarczy, że opiszesz, co nie działa albo co chcecie zmienić — własnymi słowami. Resztę dopytamy w rozmowie.</p>
<h3>Jak szybko się odezwiecie?</h3>
<p>Na zgłoszenie z formularza odpowiadamy w ciągu 3 dni roboczych, wybranym przez Ciebie kanałem kontaktu.</p>
<h3>Czy możemy zacząć od jednej sprawy?</h3>
<p>Tak. Wiele organizacji zaczyna od jednego obszaru, np. poczty albo strony, i rozszerza współpracę, gdy zobaczy efekty.</p>
<h3>Ile to kosztuje?</h3>
<p>Rozliczamy się stałym, miesięcznym abonamentem, którego wysokość zależy od potrzeb i skali organizacji. Szczegóły znajdziesz w zakładce „Ile to kosztuje?”.</p>
<h3>Czy będziecie mieć dostęp do naszych danych?</h3>
<p>Tylko w zakresie potrzebnym do pracy. Zasady dostępu i ochrony danych osobowych zapisujemy w umowie.</p>
<h3>Czy pomożecie z dostępnością strony i deklaracją dostępności?</h3>
<p>Tak. Sprawdzamy stronę i dokumenty pod kątem WCAG, pomagamy przygotować deklarację dostępności i wprowadzić poprawki.</p>
HTML],
        ];
    }
}
