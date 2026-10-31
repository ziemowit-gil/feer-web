<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\UniqueSlug;
use Illuminate\Database\Seeder;

/**
 * Dedykowane podstrony witryny „Szkolenia” (slug szkolenia, domena szkolenia.feer.org.pl) dla instytucji i firm
 * szkoleniowych: strona nadrzędna z listą podstron oraz trzy podstrony (szkolenia dla instytucji, współpraca z firmami
 * szkoleniowymi, jak wygląda współpraca).
 *
 * Wszystkie strony powstają jako SZKICE (nieopublikowane): treść opiera się wyłącznie na faktach, które fundacja już
 * publikuje (opis szkoleń, zasady współpracy z instytucjami, odpowiedzi w FAQ), bez cen i obietnic. Do przejrzenia
 * i uzupełnienia w panelu (przełącz się na witrynę „Szkolenia”) przed publikacją.
 *
 * Idempotentny: strony o tym samym tytule w tej witrynie nie są duplikowane ani nadpisywane.
 *
 *   php artisan db:seed --class=SzkoleniaInstitutionPagesSeeder --force
 */
class SzkoleniaInstitutionPagesSeeder extends Seeder
{
    public function run(): void
    {
        $site = SiteSetting::query()->notTrashed()->where('slug', 'szkolenia')->first();

        if (! $site) {
            $this->command?->warn('Pominięto: brak witryny o slugu „szkolenia”.');

            return;
        }

        $created = 0;
        $parent = $this->ensure($site, null, 'Dla instytucji i firm szkoleniowych', 0, self::parentHtml(), 'Jak współpracujemy z instytucjami i firmami szkoleniowymi: szkolenia, partnerstwa i zasady współpracy.', $created);

        foreach ([
            ['Szkolenia dla instytucji', self::trainingHtml(), 'Szkolenia z obsługi komputera, urządzeń mobilnych i sprzętu wspomagającego dla osób z dysfunkcją wzroku.'],
            ['Współpraca z firmami szkoleniowymi', self::partnersHtml(), 'Jak możemy współpracować z firmami szkoleniowymi: konsultacje dostępności, wsparcie merytoryczne i wspólne projekty.'],
            ['Jak wygląda współpraca', self::processHtml(), 'Jak się z nami skontaktować i jak ustalamy zasady współpracy.'],
        ] as $i => [$title, $html, $meta]) {
            $this->ensure($site, $parent, $title, $i + 1, $html, $meta, $created);
        }

        $this->command?->info($created > 0
            ? "Szkolenia: utworzono {$created} szkiców stron dla instytucji i firm szkoleniowych (do przejrzenia i publikacji w panelu)."
            : 'Pominięto: strony dla instytucji i firm szkoleniowych już istnieją.');
    }

    private function ensure(SiteSetting $site, ?Page $parent, string $title, int $order, string $html, string $meta, int &$created): Page
    {
        $existing = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('title', $title)->first();

        if ($existing) {
            return $existing;
        }

        $created++;

        return Page::withoutGlobalScopes()->create([
            'site_id' => $site->id,
            'parent_id' => $parent?->id,
            'title' => $title,
            'slug' => UniqueSlug::page($title),
            'type' => 'standard',
            'content' => $html,
            'is_published' => false,
            'show_in_menu' => true,
            'order' => $order,
            'meta_title' => $title,
            'meta_description' => $meta,
        ]);
    }

    private static function parentHtml(): string
    {
        return <<<'HTML'
<p>Fundacja FEER prowadzi szkolenia z obsługi komputera, urządzeń mobilnych oraz specjalistycznego sprzętu i oprogramowania dla osób z dysfunkcją wzroku. Współpracujemy też z instytucjami i organizacjami, które chcą wdrażać standardy dostępności.</p>
<p>Na tych stronach opisujemy, jak możemy współpracować z instytucjami i firmami szkoleniowymi.</p>
<p>[podstrony]</p>
HTML;
    }

    private static function trainingHtml(): string
    {
        return <<<'HTML'
<p>Prowadzimy szkolenia z obsługi komputera, urządzeń mobilnych oraz specjalistycznego sprzętu i oprogramowania dla osób z dysfunkcją wzroku. Zajęcia są indywidualne i dopasowane do potrzeb uczestnika.</p>
<h2>Zakres szkoleń</h2>
<ul>
<li><strong>Komputer:</strong> obsługa systemów operacyjnych dla osób niewidomych i słabowidzących.</li>
<li><strong>Urządzenia mobilne:</strong> obsługa smartfonów i tabletów, w tym produktów Apple.</li>
<li><strong>Sprzęt specjalistyczny:</strong> konfiguracja czytników książek, powiększalników i innych urządzeń wspomagających.</li>
<li><strong>Oprogramowanie:</strong> instalacja i konfiguracja czytników ekranu oraz programów powiększających.</li>
</ul>
<h2>Dla kogo</h2>
<p>Jeśli Twoja instytucja szuka szkolenia dla osób z dysfunkcją wzroku, napisz do nas. Zakres i formę szkolenia ustalamy po rozmowie.</p>
<p><a href="/kontakt">Napisz do nas</a></p>
HTML;
    }

    private static function partnersHtml(): string
    {
        return <<<'HTML'
<p>Współpracujemy z instytucjami, organizacjami i partnerami na różnych zasadach, zależnie od potrzeb i kontekstu. Jeśli prowadzisz firmę szkoleniową, możemy współpracować na przykład przy:</p>
<ul>
<li>konsultacji materiałów, wydarzeń i usług pod kątem dostępności,</li>
<li>wsparciu merytorycznym i eksperckim: dostępność, nowoczesne technologie, prawa osób z niepełnosprawnościami,</li>
<li>wspólnych projektach i inicjatywach społecznych,</li>
<li>dzieleniu się wiedzą i materiałami edukacyjnymi.</li>
</ul>
<h2>Nabory trenerów</h2>
<p>Nabory na trenerów prowadzimy okresowo, zależnie od potrzeb i realizowanych projektów. Informacje o otwartych naborach publikujemy na naszej stronie internetowej.</p>
<p><a href="/kontakt">Napisz do nas</a></p>
HTML;
    }

    private static function processHtml(): string
    {
        return <<<'HTML'
<p>Napisz do nas przez formularz kontaktowy i opisz, czego szukasz. Odpowiadamy zwykle w ciągu jednego dnia roboczego.</p>
<p>Zakres i zasady współpracy ustalamy indywidualnie. Nie ogłaszamy naborów: partnerstwa nawiązujemy stopniowo, na podstawie relacji i wspólnych wartości.</p>
<p><a href="/kontakt">Przejdź do formularza kontaktowego</a></p>
HTML;
    }
}
