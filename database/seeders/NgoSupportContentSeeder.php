<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;

/**
 * Treść działania „Wsparcie technologiczne dla NGO” (/dzialania/wsparcie-ngo): podstrony w zakładkach /
 * menu bocznym — „Zakres wsparcia”, „Jak wygląda współpraca”, „Najczęstsze pytania”.
 * „Zakres wsparcia”, „Jak wygląda współpraca”, „Ile to kosztuje?” i „Kontakt” (osoba kontaktowa działania) są zbudowane z bloków (edytor blokowy): kafle obszarów z ikonami
 * i numerowane kroki — płasko, kolory marki. Wcześniejszą, tekstową wersję tych dwóch stron z tego seedera
 * podmienia, o ile nikt jej nie edytował w panelu (updated_at = created_at). „Ile to kosztuje?” wpisaną ręcznie
 * (trzy zasady, bez bloków) przebudowuje raz — dopisuje kalkulację i informację o wykonawcy (ziemowitgil.pl).
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

        // Kontakt i opis dla wyszukiwarek — tylko gdy puste (albo zastępczy „x”), żeby nie nadpisać zmian z panelu.
        $updates = [];
        if (blank($project->coordinator_email)) {
            $updates += ['coordinator_name' => $project->coordinator_name ?: 'Ziemowit Gil', 'coordinator_email' => 'ziemowit.gil@feer.org.pl', 'show_coordinator' => true];
        }
        if (blank($project->coordinator_phone)) {
            $updates += ['coordinator_phone' => '601 350 487', 'coordinator_phone_hours' => 'w poniedziałki 15:00–17:00'];
        }
        if (mb_strlen(trim((string) $project->meta_description)) < 20) {
            $updates['meta_description'] = 'Wsparcie technologiczne dla NGO: Microsoft 365 i Google Workspace, strony WWW, bezpieczeństwo, hosting i dostępność WCAG — w stałym, miesięcznym abonamencie.';
        }
        if ($updates) {
            $project->forceFill($updates)->save();
            $this->command?->info('Uzupełniono działanie: '.implode(', ', array_keys($updates)).'.');
        }

        foreach ($this->blockPages() as $slug => [$title, $order, $blocks]) {
            $blocks = BlockRenderer::normalize($blocks);
            $html = BlockRenderer::render($blocks);
            $page = Page::withTrashed()->where('slug', $slug)->first();
            $isOriginalPricing = $page && $slug === 'wsparcie-ngo-ile-to-kosztuje' && ! $page->blocks
                && str_contains((string) $page->content, 'przejrzystość relacji') && ! str_contains((string) $page->content, 'ziemowitgil.pl');
            if ($page && ! $isOriginalPricing && ! $page->created_at?->equalTo($page->updated_at)) {
                $this->command?->line("Podstrona „{$page->title}” była zmieniana w panelu — bez zmian.");

                continue;
            }
            $page ??= new Page;
            $page->forceFill([
                'site_id' => $project->site_id,
                'title' => $title,
                'slug' => $slug,
                'blocks' => $blocks,
                'content' => $html,
                'type' => 'standard',
                'project_id' => $project->id,
                'project_display' => 'tab',
                'is_published' => true,
                'order' => $order,
            ]);
            $page->save();
            // Kolejne uruchomienie ma rozpoznać stronę jako „nieedytowaną” (bloki ustawione przez seeder).
            Page::withoutTimestamps(fn () => $page->forceFill(['updated_at' => $page->created_at])->saveQuietly());
            $this->command?->info("Zapisano podstronę „{$title}” (bloki).");
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

    /** @return array<string, array{0: string, 1: int, 2: array}> slug => [tytuł, kolejność, bloki] */
    private function blockPages(): array
    {
        $p = fn (string $text, bool $lead = false) => ['type' => 'paragraph', 'attrs' => ['text' => $text, 'lead' => $lead]];
        $html = fn (string $html) => ['type' => 'html', 'attrs' => ['html' => $html]];
        $formButton = ['type' => 'buttons', 'attrs' => ['items' => [['label' => 'Wypełnij formularz zgłoszeniowy', 'url' => '/formularz/wsparcie-technologiczne-ngo', 'variant' => 'brand']]]];

        $areas = [
            ['mail', 'Microsoft 365 i Google Workspace', 'Poczta na domenie organizacji, konta i uprawnienia, dyski współdzielone, przeniesienie z prywatnych skrzynek i wsparcie zespołu na co dzień.'],
            ['web', 'Strona WWW i CMS', 'Aktualizacje, poprawki, nowe podstrony i formularze, porządek w treściach — albo nowa strona, którą łatwo prowadzić samodzielnie.'],
            ['shield_lock', 'Bezpieczeństwo i kopie zapasowe', 'Dwuetapowe logowanie, menedżer haseł, regularne kopie i sprawdzanie, czy da się z nich odtworzyć dane.'],
            ['dns', 'Serwery i hosting', 'Wybór i konfiguracja hostingu, domeny i certyfikaty SSL, przenosiny strony lub poczty, reagowanie na awarie.'],
            ['automation', 'Automatyzacja procesów', 'Formularze, które same trafiają do arkusza lub CRM, przypomnienia i obieg dokumentów — mniej przepisywania i pomyłek.'],
            ['accessibility_new', 'Dostępność cyfrowa (WCAG)', 'Sprawdzenie strony i dokumentów, deklaracja dostępności, poprawki i szkolenie z tworzenia dostępnych treści. To nasza specjalność.'],
        ];
        $cards = implode('', array_map(fn ($a) => '<li class="ngo-row"><span class="ngo-ico material-symbols-outlined" aria-hidden="true">'.$a[0].'</span><div><h3>'.$a[1].'</h3><p>'.$a[2].'</p></div></li>', $areas));

        $steps = [
            ['Zgłoszenie', 'Wypełniasz formularz zgłoszeniowy — to kilka minut. Numer KRS pozwoli nam od razu uzupełnić dane organizacji.'],
            ['Rozmowa', 'W ciągu 3 dni roboczych kontaktujemy się wybranym przez Ciebie kanałem i dopytujemy o szczegóły.'],
            ['Diagnoza i propozycja', 'Sprawdzamy, co działa, a co wymaga zmian, i przygotowujemy propozycję zakresu wsparcia oraz miesięcznego abonamentu.'],
            ['Umowa', 'Ustalamy zasady współpracy, w tym dostęp do systemów i ochronę danych osobowych.'],
            ['Stałe wsparcie', 'Zgłaszasz potrzeby wtedy, kiedy się pojawiają, a my dbamy też o to, czego na co dzień nie widać: aktualizacje, kopie i bezpieczeństwo.'],
        ];
        $stepsHtml = implode('', array_map(fn ($i, $st) => '<li class="ngo-step"><span class="ngo-num" aria-hidden="true">'.($i + 1).'</span><div><h3><span class="sr-only">Krok '.($i + 1).': </span>'.$st[0].'</h3><p>'.$st[1].'</p></div></li>', array_keys($steps), $steps));

        $principles = [
            ['Indywidualnie', 'stawkę ustalamy na podstawie rzeczywistych potrzeb i skali Waszej organizacji.'],
            ['Przewidywalnie', 'rozliczamy się stałym, miesięcznym abonamentem.'],
            ['Bez niespodzianek', 'żadnych ukrytych opłat, żadnych dodatkowych wydatków.'],
        ];
        $principlesHtml = implode('', array_map(fn ($i, $pr) => '<li class="ngo-step"><span class="ngo-num" aria-hidden="true">'.($i + 1).'</span><div><h3>'.$pr[0].'</h3><p>'.ucfirst($pr[1]).'</p></div></li>', array_keys($principles), $principles));

        // Wspólny styl w zwykłym CSS (nie zależy od klas Tailwinda): płasko, bez ramek, tło #f3f4f6, akcent #1752bf (≥ 4,5:1).
        $css = '<style>'
            .'.ngo-cards,.ngo-steps,.ngo-rows{list-style:none;margin:1.5rem 0;padding:0;display:grid;gap:1rem}'
            .'.ngo-cards{grid-template-columns:repeat(auto-fit,minmax(18rem,1fr))}'
            .'.ngo-card,.ngo-step{margin:0;border-radius:.75rem;background:#f3f4f6;padding:1.25rem 1.25rem 1.1rem}'
            .'.ngo-card::before,.ngo-step::before,.ngo-row::before{content:none}'
            .'.ngo-rows{gap:.5rem}'
            .'.ngo-row{display:flex;gap:1.1rem;align-items:flex-start;margin:0;border-radius:.75rem;background:#f3f4f6;padding:1.1rem 1.25rem}'
            .'.prose .ngo-row h3{margin:.15rem 0 .3rem;font-size:1.1rem;line-height:1.3;color:#1d1d1a}'
            .'.prose .ngo-row p{margin:0;font-size:.975rem;line-height:1.6;color:#1d1d1a}'
            .'.ngo-row .ngo-ico{flex:none}'
            .'.prose .ngo-ico,.ngo-ico{display:inline-flex;width:2.75rem;height:2.75rem;align-items:center;justify-content:center;border-radius:.6rem;background:#e8f0ff;color:#1752bf;font-size:1.75rem;line-height:1}'
            .'.prose .ngo-card h3,.prose .ngo-step h3{margin:.75rem 0 .35rem;font-size:1.1rem;line-height:1.3;color:#1d1d1a}'
            .'.prose .ngo-card p,.prose .ngo-step p{margin:0;font-size:.975rem;line-height:1.6;color:#1d1d1a}'
            .'.prose .ngo-card a{color:#1752bf;font-weight:700;text-decoration:underline;text-underline-offset:3px;overflow-wrap:anywhere}'
            .'.ngo-step{display:flex;gap:1.1rem;align-items:flex-start;padding:1.1rem 1.25rem}'
            .'.ngo-num{flex:none;display:inline-flex;width:2.75rem;height:2.75rem;align-items:center;justify-content:center;border-radius:.6rem;background:#e8f0ff;color:#1752bf;font-weight:800;font-size:1.25rem}'
            .'.ngo-steps{gap:.5rem}'
            .'.prose .ngo-step h3{margin:.15rem 0 .3rem}'
            .'</style>';

        // Kontakt: wyłącznie dane tego działania (osoba kontaktowa z ustawień działania), nie ogólne dane fundacji.
        $project = Project::where('slug', self::PROJECT_SLUG)->first();
        $name = trim((string) ($project?->coordinator_name ?? ''));
        $email = trim((string) ($project?->coordinator_email ?? ''));
        $phone = trim((string) ($project?->coordinator_phone ?? ''));
        $phoneHours = trim((string) ($project?->coordinator_phone_hours ?? ''));
        // Czysty tekst: etykieta pogrubiona, wartość obok (linki mailto:/tel:).
        $contactLines = array_filter([
            $name !== '' ? '<strong>Osoba kontaktowa:</strong> '.e($name) : null,
            $email !== '' ? '<strong>E-mail:</strong> <a href="mailto:'.e($email).'">'.e($email).'</a>' : null,
            $phone !== '' ? '<strong>Telefon:</strong> <a href="tel:'.e(preg_replace('/[^0-9+]/', '', $phone)).'">'.e($phone).'</a>'.($phoneHours !== '' ? ' ('.e($phoneHours).')' : '') : null,
        ]);

        return [
            'wsparcie-ngo-kontakt' => ['Kontakt', 80, array_values(array_filter([
                $p('Masz pytanie o wsparcie technologiczne dla NGO, zanim wypełnisz zgłoszenie? Napisz albo zadzwoń — chętnie doradzimy, od czego zacząć.', true),
                $contactLines ? $p(implode('<br>', $contactLines)) : null,
                $p('Jeśli wiesz już, czego potrzebujesz, najszybciej będzie przez formularz zgłoszeniowy.'),
                $formButton,
            ]))],
            'wsparcie-ngo-ile-to-kosztuje' => ['Ile to kosztuje?', 30, [
                $p('Wierzymy w przejrzystość relacji. Dlatego nasze wsparcie techniczne opiera się na trzech zasadach:', true),
                $html($css.'<ol class="ngo-steps" role="list">'.$principlesHtml.'</ol>'),
                $p('Wszystko kalkulujemy indywidualnie — zależnie od potrzeb Waszej organizacji: liczby osób i stanowisk, systemów, z których korzystacie, oraz zakresu wsparcia. Wycenę przygotowujemy po rozmowie i diagnozie, zanim podpiszemy umowę.'),
                ['type' => 'heading', 'attrs' => ['text' => 'Kto udziela wsparcia', 'level' => 3]],
                $p('Wsparcia technologicznego udziela <a href="https://ziemowitgil.pl">ziemowitgil.pl</a> — <strong>Ziemowit Gil</strong>, tyfloinformatyk, edukator i prezes Fundacji FEER. Od ponad siedmiu lat łączy nowoczesne technologie z sektorem pozarządowym i edukacją, bo wierzy, że dobrze zaprojektowane narzędzie otwiera drzwi, których wcześniej nie było.'),
                $p('Na co dzień zajmuje się dostępnością cyfrową, automatyzacją procesów i uczeniem technologii dzieci oraz młodzieży z niepełnosprawnościami. Organizacji nie zostawia samej z wiedzą — działa razem z nią.'),
                $formButton,
            ]],
            'wsparcie-ngo-zakres-wsparcia' => ['Zakres wsparcia', 10, [
                $p('Pomagamy w tym, co w organizacji „musi działać”, żeby zespół mógł zająć się misją. Wsparcie dobieramy do Waszych potrzeb — możecie zacząć od jednego obszaru.', true),
                $html($css.'<ul class="ngo-rows" role="list">'.$cards.'</ul>'),
                $p('Nie widzisz swojego problemu na liście? Opisz go w formularzu — wybierz <strong>„Inne”</strong>.'),
                $formButton,
            ]],
            'wsparcie-ngo-jak-wyglada-wspolpraca' => ['Jak wygląda współpraca', 20, [
                $p('Od zgłoszenia do stałego wsparcia w pięciu krokach. Przez cały czas masz jedną osobę kontaktową, która zna Waszą organizację i jej systemy.', true),
                $html($css.'<ol class="ngo-steps" role="list">'.$stepsHtml.'</ol>'),
                $formButton,
            ]],
        ];
    }

    /** @return array<string, array{0: string, 1: int, 2: string}> slug => [tytuł, kolejność, treść HTML] */
    private function pages(): array
    {
        return [
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
