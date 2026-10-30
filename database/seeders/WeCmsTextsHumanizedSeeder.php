<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Project;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów podstron działania weCMS (slugi `wecms-*`): myślniki jako spójniki zastąpione dwukropkiem,
 * przecinkiem lub kropką, usunięte konstrukcje „nie X, a Y” i zdanie-wypełniacz. Dodatkowo tytuły działań
 * „weCMS — …” i „Dostępność w praktyce — …” dostają dwukropek zamiast myślnika. Fakty bez zmian.
 *
 * Podmienia pojedyncze fragmenty (dokładne dopasowanie), więc jest idempotentny i nie nadpisze tekstu poprawionego
 * później w panelu. Dla stron z blokami odświeża też `content`.
 *
 *   php artisan db:seed --class=WeCmsTextsHumanizedSeeder --force
 */
class WeCmsTextsHumanizedSeeder extends Seeder
{
    /** @return array<string, string> stary fragment => nowy fragment */
    private function replacements(): array
    {
        return [
            // O CMS
            'do października 2026 roku — i będziemy' => 'do października 2026 roku i będziemy',
            '<strong>CMS — system do zarządzania treścią</strong>' => '<strong>CMS, czyli system do zarządzania treścią</strong>',
            'odwiedzający — <strong>szablon strony — piszemy indywidualnie dla każdej organizacji</strong>' => 'odwiedzający (<strong>szablon strony</strong>) <strong>piszemy indywidualnie dla każdej organizacji</strong>',
            '<h2>System do zarządzania treścią, nie gotowy wygląd</h2>' => '<h2>Zarządzanie treścią i indywidualny wygląd</h2>',
            'darowizny, newsletter, formularze czy BIP są częścią systemu, a nie dodatkami z różnych źródeł.' => 'darowizny, newsletter, formularze i BIP są wbudowane w system.',
            // Funkcjonalności
            'Poniżej krótki przegląd — szczegóły' => 'Poniżej krótki przegląd. Szczegóły',
            // Wersje
            '<strong>wersję płatną</strong> — dla organizacji, które' => '<strong>wersję płatną</strong>, przeznaczoną dla organizacji, które',
            'a w panelu można je przeglądać — wyłączają się tylko nowe wpisy' => 'a w panelu można je przeglądać. Wyłączają się tylko nowe wpisy',
            // Wymagania
            'Aktualna przeglądarka — Chrome' => 'Aktualna przeglądarka: Chrome',
            // Strony i struktura
            'Publikacja z wyprzedzeniem — strona pojawia' => 'Publikacja z wyprzedzeniem: strona pojawia',
            'tydzień po terminie i inne — każde wysyłane raz.' => 'tydzień po terminie i inne, każde wysyłane raz.',
            // Edytory, zespół, moduły, integracje
            'Blokada równoczesnej edycji — system ostrzega' => 'Blokada równoczesnej edycji: system ostrzega',
            'redaktor PR i edytor BIP — każda z dostępem' => 'redaktor PR i edytor BIP, każda z dostępem',
            'Moduły włączasz w ustawieniach — wyłączony moduł znika' => 'Moduły włączasz w ustawieniach. Wyłączony moduł znika',
            'synchronizacja z SZO — wykonywane automatycznie' => 'synchronizacja z SZO, wykonywane automatycznie',
            'połączenia — w panelu i jednym poleceniem' => 'połączenia, w panelu i jednym poleceniem',
            // Dostępność
            'Dostępność jest wbudowana w system, a nie dodawana na końcu.' => 'Dostępność jest wbudowana w system od początku.',
            // Demo i zamówienie
            'w weCMS — bez instalowania czegokolwiek' => 'w weCMS, bez instalowania czegokolwiek',
            'napisz do nas — pokażemy go' => 'napisz do nas, pokażemy go',
            'Wypełnij formularz — odezwiemy się' => 'Wypełnij formularz, a odezwiemy się',
            // Podpisy zdjęć
            '<figcaption>Panel redakcji — pulpit' => '<figcaption>Panel redakcji: pulpit',
            '<figcaption>Strona główna feer.org.pl — działa' => '<figcaption>Strona główna feer.org.pl: działa',
            '<figcaption>Drzewo stron — przeciąganie' => '<figcaption>Drzewo stron: przeciąganie',
            '<figcaption>Dziennik zdarzeń — kto' => '<figcaption>Dziennik zdarzeń: kto',
            '<figcaption>Newsletter — kampanie' => '<figcaption>Newsletter: kampanie',
            '<figcaption>Kreator formularzy — pole' => '<figcaption>Kreator formularzy: pole',
            '<figcaption>Formularz z kreatora na stronie — kroki' => '<figcaption>Formularz z kreatora na stronie: kroki',
            '<figcaption>BIP — osobna witryna' => '<figcaption>BIP: osobna witryna',
            // Etykiety z listy „Na czym nam zależało” i podobne (znacznik zamykający + myślnik)
            '</strong> — ' => '</strong>: ',
        ];
    }

    public function run(): void
    {
        $map = $this->replacements();
        $changedPages = 0;

        foreach (Page::withoutGlobalScopes()->where('slug', 'like', 'wecms-%')->get() as $page) {
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
                $changedPages++;
            }
        }

        $titles = [
            'weCMS — system do stron organizacji społecznych' => 'weCMS: system do stron organizacji społecznych',
            'Dostępność w praktyce — współpraca i partnerstwa' => 'Dostępność w praktyce: współpraca i partnerstwa',
        ];
        $changedTitles = 0;
        foreach (Project::withoutGlobalScopes()->whereIn('title', array_keys($titles))->get() as $project) {
            $project->title = $titles[$project->title];
            $project->save();
            $changedTitles++;
        }

        $this->command?->info("weCMS: zmieniono podstron {$changedPages}, tytułów działań {$changedTitles}.");
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
