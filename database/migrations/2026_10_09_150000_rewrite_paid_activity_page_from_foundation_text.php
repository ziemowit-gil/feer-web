<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Przepisuje stronę „Odpłatna działalność pożytku publicznego" na podstawie tekstu Fundacji (zasada otwartości,
 * cena = suma kosztów, na co idą wpłaty, zasady płatności) — prostym językiem i BEZ numerów kont i telefonów.
 * Zmienia treść tylko wtedy, gdy strona nadal ma wersję wygenerowaną automatycznie (nie była edytowana ręcznie).
 */
return new class extends Migration
{
    public function up(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();
        if (! $page || ! str_contains((string) $page->content, '<strong>Działania płatne.</strong>')) {
            return;
        }

        $page->content = <<<'HTML'
<p><strong>Krótko:</strong> Fundacja FEER nie zarabia na swoich działaniach. Nie prowadzimy działalności gospodarczej i na nikim nie robimy zysku. Gdy bierzemy opłatę, pokrywa ona tylko koszty.</p>

<h2>Dlaczego niektóre działania są płatne?</h2>
<p>Kierujemy się zasadą pełnej otwartości. Często pytasz, dlaczego niektóre inicjatywy wymagają opłat. Wyjaśniamy to poniżej.</p>
<p>Większość naszej pracy jest całkowicie <strong>bezpłatna</strong>. Dzieje się tak dzięki dotacjom, wsparciu wolontariuszy oraz naszej własnej chęci i zapałowi do pracy. Dzięki temu możemy robić takie projekty jak warsztaty „Zrozumieć ADHD” bez żadnych kosztów dla uczestników.</p>
<p>Niektóre działania, na przykład wybrane kursy, szkolenia specjalistyczne czy certyfikaty, musimy robić jako <strong>działalność odpłatną</strong>. To te działania, na które nie mamy środków albo mamy ich za mało. Cena takiej usługi to tylko suma kosztów, które musimy ponieść, żeby mogła się odbyć.</p>

<h2>Na co idą Twoje wpłaty?</h2>
<p>Twoje wpłaty pokrywają wyłącznie prawdziwe wydatki:</p>
<ul>
<li>wynagrodzenie prowadzącego za jego czas i wiedzę,</li>
<li>opłacenie platformy edukacyjnej i infrastruktury technicznej,</li>
<li>przygotowanie i bezpieczną wysyłkę certyfikatów i zaświadczeń.</li>
</ul>
<p>Dzięki współpracy z partnerami staramy się obniżać te kwoty do minimum. Działalność odpłatna pozwala nam działać od razu, gdy pojawia się potrzeba, bez czekania na granty i dotacje, które nie zawsze są dostępne na dany temat.</p>

<h2>Co to jest odpłatna działalność pożytku publicznego?</h2>
<p>Prawo pozwala fundacjom pobierać opłaty za usługi, które pomagają ludziom i pasują do celów fundacji. Nazywa się to odpłatną działalnością pożytku publicznego. To nie jest zwykły biznes, bo nie robimy tego dla zysku. Zasady opisuje ustawa o działalności pożytku publicznego i o wolontariacie.</p>

<h2>Jak płacić za płatne działania?</h2>
<p>Każdą wpłatę sprawdzamy ręcznie, dlatego prosimy o stosowanie się do czterech zasad.</p>
<ol>
<li><strong>Wybierz właściwe konto.</strong> Od 1 stycznia 2026 roku mamy dwa osobne konta bankowe. Jedno służy tylko do darowizn, czyli dobrowolnego wsparcia naszej misji i bieżącej pracy fundacji. Drugie służy tylko do opłat za szkolenia, kursy i certyfikaty. Numery kont podajemy w mailu z fakturą. Opłaty wpłacaj tylko na konto do opłat.</li>
<li><strong>Płać po otrzymaniu faktury.</strong> Prosimy o niedokonywanie przedpłat. Fakturę VAT wyślemy mailem dopiero po potwierdzeniu zgłoszenia na dane wydarzenie.</li>
<li><strong>Wybierz metodę płatności.</strong> Możesz zapłacić zwykłym przelewem albo szybką płatnością BLIK. Dane do płatności znajdziesz w mailu z fakturą. BLIK nie jest dostępny przy rozliczeniach z dofinansowaniem PFRON.</li>
<li><strong>Wpisz prawidłowy tytuł przelewu.</strong> W tytule wpisz tylko numer faktury (na przykład FSP/1/01/2026). Nie dodawaj imion, nazwisk ani nazwy szkolenia. Dzięki temu szybciej sprawdzimy wpłatę i przygotujemy dokumenty.</li>
</ol>

<h2>Masz pytania?</h2>
<p>Napisz do nas, chętnie wszystko wyjaśnimy. Dane kontaktowe znajdziesz na stronie <a href="/kontakt">Kontakt</a>.</p>
HTML;
        $page->save();
    }

    public function down(): void
    {
        // Bez cofania — to przepisanie treści.
    }
};
