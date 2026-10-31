<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Strona „Odpłatna działalność pożytku publicznego” (slug odplatna-dzialalnosc-pozytku-publicznego): treść uporządkowana
 * w czytelne sekcje. Na górze krótka definicja: w formie odpłatnej część kosztów pokrywa Beneficjent (osoba korzystająca
 * z działania), potem wyjaśnienie, dlaczego tak jest, na co idą wpłaty, podstawa prawna i cztery zasady płatności.
 * Fakty bez zmian (tekst z dotychczasowej strony i FAQ), dochodzi tylko określenie roli Beneficjenta.
 *
 * Idempotentny: przepisuje stronę tylko wtedy, gdy ma jeszcze dotychczasową strukturę (jest „Jak płacić za płatne działania”
 * i nie ma słowa „Beneficjent”), więc nie nadpisze treści zmienionej później w panelu.
 *
 *   php artisan db:seed --class=PaidActivityPageRewriteSeeder --force
 */
class PaidActivityPageRewriteSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();

        if (! $page) {
            $this->command?->warn('Pominięto: brak strony o odpłatnej działalności.');

            return;
        }

        $content = (string) $page->content;

        if (! str_contains($content, 'Jak płacić za płatne działania') || str_contains($content, 'Beneficjent')) {
            $this->command?->line('Pominięto: strona była już zmieniana.');

            return;
        }

        $page->content = self::html();
        $page->save();
        $this->command?->info('Przepisano stronę o odpłatnej działalności.');
    }

    public static function html(): string
    {
        return <<<'HTML'
<p><strong>Krótko:</strong> Fundacja FEER nie zarabia na swoich działaniach. W formie odpłatnej <strong>Beneficjent</strong>, czyli osoba lub organizacja, która korzysta z działania, pokrywa część jego kosztów. Opłata nie jest zyskiem fundacji. Idzie wyłącznie na koszty.</p>

<h2>Co znaczy „odpłatne”?</h2>
<p>Odpłatne działanie to takie, w którym część kosztów pokrywa Beneficjent. Pozostałą część pokrywa fundacja, m.in. dzięki dotacjom i darowiznom.</p>
<p>Nie prowadzimy działalności gospodarczej i na nikim nie robimy zysku. Opłata Beneficjenta to tylko jego wkład w koszty działania.</p>

<h2>Dlaczego tak jest?</h2>
<p>Większość naszej pracy jest całkowicie <strong>bezpłatna</strong>. Możliwe to dzięki dotacjom, wsparciu wolontariuszy i naszemu zaangażowaniu. Dzięki temu możemy prowadzić projekty takie jak warsztaty „Zrozumieć ADHD” bez żadnych kosztów dla uczestników.</p>
<p>Niektóre działania, na przykład wybrane kursy, szkolenia specjalistyczne czy certyfikaty, prowadzimy jako <strong>działalność odpłatną</strong>. To te działania, na które nie mamy środków albo mamy ich za mało. Bez wkładu Beneficjenta by się nie odbyły.</p>
<p>Odpłatna formuła pozwala nam też działać od razu, gdy pojawia się potrzeba, bez czekania na granty i dotacje, które nie zawsze są dostępne na dany temat.</p>

<h2>Na co idą wpłaty?</h2>
<p>Wpłaty Beneficjentów pokrywają wyłącznie rzeczywiste wydatki:</p>
<ul>
<li>wynagrodzenie prowadzącego za jego czas i wiedzę,</li>
<li>opłacenie platformy edukacyjnej i infrastruktury technicznej,</li>
<li>przygotowanie i bezpieczną wysyłkę certyfikatów i zaświadczeń.</li>
</ul>
<p>Dzięki współpracy z partnerami staramy się obniżać te kwoty do minimum.</p>

<h2>Czym jest odpłatna działalność pożytku publicznego?</h2>
<p>Prawo pozwala fundacjom pobierać opłaty za usługi, które pomagają ludziom i pasują do celów fundacji. Nazywa się to odpłatną działalnością pożytku publicznego. To nie jest zwykły biznes, bo nie robimy tego dla zysku. Zasady opisuje ustawa o działalności pożytku publicznego i o wolontariacie.</p>

<h2>Jak płacić za płatne działania?</h2>
<p>Każdą wpłatę sprawdzamy ręcznie, dlatego prosimy o stosowanie się do czterech zasad.</p>
<ol>
<li><strong>Wybierz właściwe konto.</strong> Od 1 stycznia 2026 roku mamy dwa osobne konta bankowe. Jedno służy tylko do darowizn, czyli dobrowolnego wsparcia naszej misji i bieżącej pracy fundacji. Drugie służy tylko do opłat za szkolenia, kursy i certyfikaty. Numery kont podajemy w mailu z fakturą. Opłaty wpłacaj tylko na konto do opłat. <strong>Osoby indywidualne</strong>, które uczestniczą w szkoleniach, mają swój <strong>indywidualny numer rachunku</strong>. <strong>Podmioty</strong> (na przykład organizacje i firmy) płacą na <strong>numer ogólny</strong>.</li>
<li><strong>Płać po otrzymaniu faktury.</strong> Prosimy o niedokonywanie przedpłat. Fakturę VAT wyślemy mailem dopiero po potwierdzeniu zgłoszenia na dane wydarzenie.</li>
<li><strong>Wybierz metodę płatności.</strong> Możesz zapłacić zwykłym przelewem albo szybką płatnością BLIK. Dane do płatności znajdziesz w mailu z fakturą. BLIK nie jest dostępny przy rozliczeniach z dofinansowaniem PFRON.</li>
<li><strong>Wpisz prawidłowy tytuł przelewu.</strong> W tytule wpisz tylko numer faktury (na przykład FSP/1/01/2026). Nie dodawaj imion, nazwisk ani nazwy szkolenia. Dzięki temu szybciej sprawdzimy wpłatę i przygotujemy dokumenty.</li>
</ol>

<h2>Masz pytania?</h2>
<p>Napisz do nas, chętnie wszystko wyjaśnimy. Dane kontaktowe znajdziesz na stronie <a href="/kontakt">Kontakt</a>.</p>
HTML;
    }
}
