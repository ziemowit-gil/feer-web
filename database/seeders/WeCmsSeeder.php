<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Działanie „weCMS” — nasz system do stron organizacji, na którym działa feer.org.pl.
 * Wersja zapowiedzi: krótko o tym, dlaczego powstał i czym jest; pełny opis, wersje
 * i warunki wdrożenia dojdą niebawem (wyróżniona ramka „Szczegóły już wkrótce”).
 *
 * Kategoria „Wsparcie dla organizacji” (podział według formy). Idempotentny (updateOrCreate po slugu).
 * Uruchomienie: php artisan db:seed --class=WeCmsSeeder --force
 */
class WeCmsSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'wsparcie-dla-organizacji'],
            ['name' => 'Wsparcie dla organizacji', 'order' => (int) Category::max('order') + 1],
        );

        $p = fn (string $html) => '<p>'.$html.'</p>';

        Project::updateOrCreate(
            ['slug' => 'wecms'],
            [
                'title' => 'weCMS — system do stron organizacji społecznych',
                'nav_title' => 'weCMS',
                'category_id' => $category->id,
                'excerpt' => 'Nasze własne narzędzie do prowadzenia stron: czysty design, dostępność od pierwszego dnia i prawie zerowe koszty. Szczegóły już wkrótce.',
                'for_whom' => 'Fundacje, stowarzyszenia, federacje i samorządy',
                'content' => $p('Stworzyliśmy weCMS, bo mieliśmy dość WordPressa: ciągłych aktualizacji wtyczek, płatnych dodatków i motywów, które trzeba łatać, żeby były dostępne. To nasze własne narzędzie — na nim działa strona, którą właśnie czytasz.')
                    .$p('Stawiamy na <strong>czysty, płaski design</strong>, w którym treść jest najważniejsza, <strong>dostępność wbudowaną w system</strong> (projektowaną pod WCAG 2.2 AA) oraz <strong>prawie zerowe koszty</strong>: wersja darmowa wystarcza do prowadzenia strony i działa na zwykłym, tanim hostingu.')
                    .$p('Darowizny online, newsletter, formularze, BIP czy sprawozdania są częścią systemu, a nie dodatkami z różnych źródeł.'),
                'kind' => 'project',
                'is_paid' => false,
                'custom_sections' => [[
                    'title' => 'Szczegóły już wkrótce',
                    'content' => $p('Przygotowujemy pełny opis weCMS: funkcje, wersję darmową i płatną oraz zasady wdrożenia dla innych organizacji. Jeśli chcesz dowiedzieć się więcej już teraz, napisz do nas.'),
                    'featured' => true,
                ]],
                'main_cta' => ['label' => 'Napisz do nas', 'url' => '/kontakt'],
                'is_published' => true,
            ],
        );
    }
}
