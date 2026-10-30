<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Redakcja tekstów strony „O nas” (slug o-nas): wstęp, statystyki, wartości, oś czasu i wstęp do dokumentów.
 * Fakty bez zmian; usunięte kontrasty „nie X, lecz Y”, ogólniki i zdania-wypełniacze, poprawione literówki
 * i cudzysłowy.
 *
 * Idempotentny: każdy tekst jest podmieniany tylko wtedy, gdy nadal ma pierwotne brzmienie, więc seeder
 * nie nadpisze zmian zrobionych później w panelu.
 *
 *   php artisan db:seed --class=AboutPageTextsHumanizedSeeder --force
 */
class AboutPageTextsHumanizedSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::withoutGlobalScopes()->where('slug', 'o-nas')->where('type', 'about')->first();

        if (! $page) {
            $this->command?->warn('Pominięto: brak strony o-nas.');

            return;
        }

        $changed = false;

        $intro = 'Świat staje się dostępny wtedy, gdy przestajemy się go bać, bo największe bariery nie tkwią w murach, lecz w braku wzajemnego zrozumienia.';
        if (str_starts_with((string) $page->about_intro, $intro)) {
            $page->about_intro = 'Świat staje się dostępny, gdy przestajemy się go bać. Największą barierą bywa brak wzajemnego zrozumienia. '
                .'W Fundacji FEER pomagamy społeczeństwu, firmom i organizacjom pozarządowym otwierać się na drugiego człowieka i tworzyć świat równych szans. '
                .'Dla nas najważniejszy jest człowiek. Niepełnosprawność to tylko jedna z jego cech i nie decyduje o jego potencjale ani wartości.';
            $changed = true;
        }

        $docs = 'Publikujemy te dokumenty, żeby każdy mógł zobaczyć, co robimy i na co idą środki. Dzięki temu partnerzy, darczyńcy i instytucje wiedzą, że gramy w otwarte karty.';
        if ($page->about_documents_intro === $docs) {
            $page->about_documents_intro = 'Publikujemy te dokumenty, żeby każdy mógł zobaczyć, co robimy i na co idą środki.';
            $changed = true;
        }

        $changed = $this->replaceInRows($page, 'about_stats', 'label', [
            'godzin przeprowadzonych szkoleń i zajęć' => 'godzin szkoleń i zajęć',
        ]) | $this->replaceInRows($page, 'about_stats', 'label', [
            'dzieciaków które poznały niepełnosprawność' => 'dzieci na warsztatach o niepełnosprawności',
            'dzieci, które poznały niepełnosprawność' => 'dzieci na warsztatach o niepełnosprawności',
        ]) | $this->replaceInRows($page, 'about_stats', 'value', [
            'więcej jak 1000' => 'ponad 1000',
        ]) | $this->replaceInRows($page, 'about_values', 'text', [
            'Słuchamy, rozumiemy i patrzymy na drugiego człowieka przez pryzmat jego możliwości.' => 'Słuchamy i staramy się zrozumieć drugiego człowieka, patrząc na jego możliwości.',
        ]) | $this->replaceInRows($page, 'about_timeline', 'text', [
            'Powstanie stowarzyszenia zwykłego, które w przyszłości przekształci się w FEER' => 'Powstaje stowarzyszenie zwykłe, które później przekształci się w FEER',
            'Nasza dzialalność zwalnia, mierzymy się z problemami.' => 'Nasza działalność zwalnia, mierzymy się z problemami.',
            'Życie pisze różne scenariusze - rozstajemy się z grupą studentów UP w Krakowie.' => 'Rozstajemy się z grupą studentów UP w Krakowie.',
            'Realizujemy pierwsze warsztaty "Osoby z niepełnosprawnościami i My" ze środków miasta Krakowa' => 'Realizujemy pierwsze warsztaty „Osoby z niepełnosprawnościami i My” ze środków miasta Krakowa',
            'Organizujemy pierwsze warsztaty: „ADHD u dzieci – zrozumieć, aby wspierać"' => 'Organizujemy pierwsze warsztaty „ADHD u dzieci – zrozumieć, aby wspierać”',
        ]) | $changed;

        if ($page->isDirty()) {
            $page->save();
            $this->command?->info('Zmieniono stronę o-nas.');
        } else {
            $this->command?->line('Pominięto o-nas: teksty były już zmieniane.');
        }
    }

    /** @param  array<string, string>  $map  stary tekst => nowy tekst */
    private function replaceInRows(Page $page, string $field, string $key, array $map): bool
    {
        $rows = $page->{$field};

        if (! is_array($rows)) {
            return false;
        }

        $changed = false;

        foreach ($rows as $i => $row) {
            if (isset($row[$key], $map[$row[$key]])) {
                $rows[$i][$key] = $map[$row[$key]];
                $changed = true;
            }
        }

        if ($changed) {
            $page->{$field} = $rows;
        }

        return $changed;
    }
}
