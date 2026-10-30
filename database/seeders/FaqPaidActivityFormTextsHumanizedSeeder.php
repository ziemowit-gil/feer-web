<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FormDefinition;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów: odpowiedzi w FAQ, strona „Odpłatna działalność pożytku publicznego” (slug
 * odplatna-dzialalnosc-pozytku-publicznego) i podpowiedzi w formularzu „Wsparcie technologiczne dla NGO”.
 * Fakty bez zmian; usunięte słowa-wypełniacze, myślniki, urwane zdanie w FAQ i literówki w znakach.
 *
 * Idempotentny: podmienia tylko dokładnie dopasowane, pierwotne teksty, więc nie nadpisze zmian z panelu.
 *
 *   php artisan db:seed --class=FaqPaidActivityFormTextsHumanizedSeeder --force
 */
class FaqPaidActivityFormTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $this->faq();
        $this->paidActivityPage();
        $this->form();
    }

    private function faq(): void
    {
        $answers = [
            'Treści są stopniowo przenoszone do nowych sekcji. Starsze publikacje znajdziesz jeszcze pod adresami https://feer-demo.2clicks.pl oraz stronati.feer.org.pl (są to wersje nieaktualizowane, służące wyłącznie do wglądu). Zależy nam na pełnej przejrzystości –' => 'Treści są stopniowo przenoszone do nowych sekcji. Starsze publikacje znajdziesz jeszcze pod adresami https://feer-demo.2clicks.pl oraz stronati.feer.org.pl (to wersje nieaktualizowane, tylko do wglądu).',
            'Większość naszych działań jest całkowicie darmowa, ale prowadzimy też działalność odpłatną, która nie ma charakteru komercyjnego i nie jest nastawiona na zysk. Profesjonalni trenerzy, eksperci oraz specjaliści IT (w tym od dostępności i tyfloinformatyki) muszą otrzymać uczciwe wynagrodzenie za swoją pracę, czas i wiedzę. Niewielkie opłaty pobieramy wyłącznie po to, aby pokryć te realne koszty i utrzymać wysoką jakość działań.' => 'Większość naszych działań jest darmowa, ale prowadzimy też działalność odpłatną, która nie ma charakteru komercyjnego i nie służy zyskowi. Trenerzy, eksperci i specjaliści IT (w tym od dostępności i tyfloinformatyki) muszą dostać uczciwe wynagrodzenie za pracę, czas i wiedzę. Niewielkie opłaty pobieramy tylko po to, żeby pokryć te koszty i utrzymać jakość działań.',
            'Wszystkie szczegóły dotyczące wsparcia naszej działalności znajdziesz na dedykowanej podstronie /wsparcie. Możesz nas wesprzeć w najwygodniejszy dla siebie sposób: tradycyjnym przelewem bankowym, szybkim przelewem online lub stawiając nam wirtualną kawę przez BuyCoffee. Każda pomoc ma znaczenie i pozwala nam dalej działać!' => 'Wszystkie szczegóły o wsparciu naszej działalności znajdziesz na podstronie /wsparcie. Możesz nas wesprzeć w wygodny dla siebie sposób: tradycyjnym przelewem bankowym, szybkim przelewem online albo wirtualną kawą przez BuyCoffee. Każda pomoc pozwala nam dalej działać.',
            'Zasadniczo nie przyjmujemy sprzętu (w szczególności komputerowego), ponieważ nie mamy możliwości jego magazynowania ani serwisowania. Jeśli jednak akurat potrzebujemy konkretnych urządzeń, zawsze jasno i wyraźnie komunikujemy to na naszej stronie oraz na Facebooku.' => 'Zasadniczo nie przyjmujemy sprzętu, w szczególności komputerowego, bo nie mamy jak go magazynować ani serwisować. Jeśli akurat potrzebujemy konkretnych urządzeń, piszemy o tym na naszej stronie i na Facebooku.',
            'Nie prowadzimy subkont ani indywidualnych zbiórek na leczenie. Nasza fundacja skupia się na edukacji, wsparciu w rozwoju, dostępności oraz rozwoju technologicznym NGO, a nie na pomocy medycznej tego typu.' => 'Nie prowadzimy subkont ani indywidualnych zbiórek na leczenie. Fundacja zajmuje się edukacją, wsparciem w rozwoju, dostępnością i rozwojem technologicznym NGO, a nie pomocą medyczną.',
            'Działanie w przestrzeni cyfrowej pozwala nam optymalizować koszty i docierać z pomocą, edukacją oraz wsparciem technologicznym do osób z całej Polski, bez barier geograficznych. Pozwala nam to uniknąć wysokich kosztów stałych i logistycznych. Prowadzimy jednak również działania stacjonarne – głównie na terenie Małopolski, gdzie organizujemy warsztaty, spotkania i panele dyskusyjne, choć wiąże się to z dodatkowymi kosztami organizacji i wynajmu przestrzeni.' => 'Działanie online pozwala nam obniżyć koszty i docierać z pomocą, edukacją i wsparciem technologicznym do osób z całej Polski, bez barier geograficznych. Dzięki temu unikamy wysokich kosztów stałych i logistycznych. Prowadzimy też działania stacjonarne, głównie w Małopolsce: warsztaty, spotkania i panele dyskusyjne. Wiążą się one z dodatkowymi kosztami organizacji i wynajmu przestrzeni.',
            'Rezygnacja z warsztatów szkolnych wynika z barier logistycznych, prawnych oraz organizacyjnych. Z perspektywy logistycznej i finansowej wejście w placówki oświatowe wiąże się ze skomplikowaną procedurą formalną, wymogami dotyczącymi bezpieczeństwa (w tym standardów ochrony małoletnich) oraz dużymi kosztami i trudnościami w koordynacji. Zamiast tego wolimy skupić się na działaniach online oraz spotkaniach tam, gdzie możemy realnie i efektywnie wspierać dorosłych, organizacje pozarządowe i społeczności w Małopolsce i całej Polsce.' => 'Zrezygnowaliśmy z warsztatów szkolnych z powodów logistycznych, prawnych i organizacyjnych. Wejście do placówek oświatowych wymaga skomplikowanej procedury formalnej i spełnienia wymogów bezpieczeństwa (w tym standardów ochrony małoletnich), a do tego jest kosztowne i trudne w koordynacji. Wolimy skupić się na działaniach online i spotkaniach tam, gdzie możemy skutecznie wspierać dorosłych, organizacje pozarządowe i społeczności w Małopolsce i całej Polsce.',
            'Tak, choć prowadzimy go rzadko, ponieważ bardzo zależy nam na zgranym i stabilnym zespole. Nasz wolontariat ma charakter głównie akcyjny. Informacje o ewentualnych naborach publikujemy na naszej podstronie wolontariat oraz na naszym profilu na Facebooku.' => 'Tak, ale rzadko, bo zależy nam na zgranym i stabilnym zespole. Nasz wolontariat ma charakter głównie akcyjny. Informacje o naborach publikujemy na podstronie wolontariat i na naszym profilu na Facebooku.',
            'To zależy – nabory na trenerów prowadzimy okresowo, w zależności od aktualnych potrzeb i realizowanych projektów. Informacje o wszelkich otwartych naborach i możliwościach współpracy trenerskiej zawsze na bieżąco publikujemy na naszej stronie internetowej' => 'To zależy. Nabory na trenerów prowadzimy okresowo, zależnie od potrzeb i realizowanych projektów. Informacje o otwartych naborach i możliwościach współpracy trenerskiej publikujemy na bieżąco na naszej stronie internetowej.',
        ];

        $changed = 0;
        foreach (Faq::withoutGlobalScopes()->get() as $faq) {
            $dirty = false;
            if (isset($answers[$faq->answer])) {
                $faq->answer = $answers[$faq->answer];
                $dirty = true;
            }
            if ($faq->question === 'Dlaczego przy niektórych działaniach pojawiają się opłaty, skoro organizacja działa non-profit ?') {
                $faq->question = 'Dlaczego przy niektórych działaniach pojawiają się opłaty, skoro organizacja działa non-profit?';
                $dirty = true;
            }
            if ($dirty) {
                $faq->save();
                $changed++;
            }
        }
        $this->command?->info("FAQ: zmieniono {$changed} wpisów.");
    }

    private function paidActivityPage(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'odplatna-dzialalnosc-pozytku-publicznego')->first();

        if (! $page) {
            $this->command?->warn('Pominięto stronę o odpłatnej działalności: brak.');

            return;
        }

        $map = [
            '<p>Kierujemy się zasadą pełnej otwartości. Często pytasz,' => '<p>Często pytasz,',
            'Dzieje się tak dzięki dotacjom, wsparciu wolontariuszy oraz naszej własnej chęci i zapałowi do pracy. Dzięki temu możemy robić takie projekty jak warsztaty' => 'Możliwe to dzięki dotacjom, wsparciu wolontariuszy i naszemu zaangażowaniu. Dzięki temu możemy prowadzić projekty takie jak warsztaty',
            'Twoje wpłaty pokrywają wyłącznie prawdziwe wydatki:' => 'Twoje wpłaty pokrywają wyłącznie rzeczywiste wydatki:',
        ];

        $content = strtr((string) $page->content, $map);
        $changed = $content !== (string) $page->content;
        $page->content = $content;

        if ($page->title === 'Odpłatna działalność pożytku publicznego — jak to u nas działa') {
            $page->title = 'Odpłatna działalność pożytku publicznego: jak to u nas działa';
            $changed = true;
        }

        if ($changed) {
            $page->save();
            $this->command?->info('Zmieniono stronę o odpłatnej działalności.');
        } else {
            $this->command?->line('Pominięto stronę o odpłatnej działalności: teksty były już zmieniane.');
        }
    }

    private function form(): void
    {
        $form = FormDefinition::withoutGlobalScopes()->where('slug', 'wsparcie-technologiczne-ngo')->first();

        if (! $form) {
            $this->command?->warn('Pominięto formularz: brak.');

            return;
        }

        $changed = false;
        $fields = $this->replaceRecursive($form->fields, ['Opcjonalnie — ' => 'Opcjonalnie: '], $changed);

        if ($changed) {
            $form->fields = $fields;
            $form->save();
            $this->command?->info('Zmieniono podpowiedzi formularza wsparcia.');
        } else {
            $this->command?->line('Pominięto formularz: teksty były już zmieniane.');
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
            $changed = $changed || $new !== $value;

            return $new;
        }

        return $value;
    }
}
