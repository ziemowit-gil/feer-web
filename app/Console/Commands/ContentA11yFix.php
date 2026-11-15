<?php

namespace App\Console\Commands;

use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Services\Content\ContentA11yFixer;
use Illuminate\Console\Command;

/**
 * Poprawiator dostępności treści z wiersza poleceń. Domyślnie tylko pokazuje,
 * co poprawi; z --zastosuj zapisuje (każdy zapis = nowa wersja w historii).
 *
 *   php artisan a11y:popraw                      # podgląd dla wszystkich treści
 *   php artisan a11y:popraw --typ=news --zastosuj
 *   php artisan a11y:popraw --regula=fake_heading --regula=fake_list --zastosuj
 */
class ContentA11yFix extends Command
{
    protected $signature = 'a11y:popraw
        {--typ=* : pages, news, projects (domyślnie wszystkie)}
        {--id= : tylko jedna treść (wymaga jednego --typ)}
        {--regula=* : tylko wybrane poprawki: heading_empty, headings, fake_heading, fake_list, table_header, iframe_title (albo reguły z raportu)}
        {--zastosuj : zapisz poprawki (bez tego tylko podgląd)}';

    protected $description = 'Poprawiator: automatycznie poprawia nagłówki, listy, tabele i osadzenia w treściach (strony, aktualności, działania)';

    private const MODELS = ['pages' => Page::class, 'news' => News::class, 'projects' => Project::class];

    public function handle(ContentA11yFixer $fixer): int
    {
        $types = $this->option('typ') ?: array_keys(self::MODELS);
        if ($bad = array_diff($types, array_keys(self::MODELS))) {
            $this->error('Nieznany typ: '.implode(', ', $bad).'. Dozwolone: pages, news, projects.');

            return self::FAILURE;
        }
        if ($this->option('id') && count($types) !== 1) {
            $this->error('--id wymaga dokładnie jednego --typ.');

            return self::FAILURE;
        }

        $fixer = $fixer->only($this->option('regula'));
        $apply = (bool) $this->option('zastosuj');
        $rows = [];
        $totals = [];

        foreach ($types as $type) {
            $query = self::MODELS[$type]::query()->whereNotNull('content')->where('content', '!=', '');
            if ($type === 'pages') {
                $query->whereNotIn('type', ['redirect', 'menu_link', 'menu_heading']);
            }
            if ($id = $this->option('id')) {
                $query->whereKey($id);
            }

            $query->orderBy('id')->each(function ($record) use ($fixer, $apply, $type, &$rows, &$totals) {
                $fixes = $fixer->fixRecord($record, $apply);
                if (! $fixes) {
                    return;
                }
                foreach ($fixes as $k => $n) {
                    $totals[$k] = ($totals[$k] ?? 0) + $n;
                }
                $rows[] = [$type, $record->id, mb_strimwidth((string) $record->title, 0, 50, '…'),
                    collect($fixes)->map(fn ($n, $k) => ContentA11yFixer::FIXES[$k].': '.$n)->implode(', ')];
            });
        }

        if (! $rows) {
            $this->info('Nic do poprawienia automatycznie.');

            return self::SUCCESS;
        }

        $this->table(['Typ', 'ID', 'Tytuł', 'Poprawki'], $rows);
        $summary = collect($totals)->map(fn ($n, $k) => ContentA11yFixer::FIXES[$k].': '.$n)->implode(', ');
        $apply
            ? $this->info('Poprawiono '.count($rows).' treści ('.$summary.'). Cofnięcie: historia wersji danej treści w panelu.')
            : $this->warn('Podgląd: '.count($rows).' treści do poprawienia ('.$summary.'). Uruchom z --zastosuj, aby zapisać.');

        return self::SUCCESS;
    }
}
