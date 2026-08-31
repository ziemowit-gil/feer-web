<?php

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Usuwa przykładowe podstrony dodane przez FederationPageTypesDemoSeeder
 * (wszystkie sluggi zaczynające się od "demo-") — do uruchomienia przed
 * przekazaniem instalacji do produkcji.
 */
class FederationCleanDemoPages extends Command
{
    protected $signature = 'federation:clean-demo {--force : Usuń bez pytania o potwierdzenie}';

    protected $description = 'Usuwa przykładowe podstrony demonstrujące typy podstron (sluggi demo-*)';

    public function handle(): int
    {
        $pages = Page::where('slug', 'like', 'demo-%')->get();

        if ($pages->isEmpty()) {
            $this->info('Brak przykładowych podstron do usunięcia.');

            return self::SUCCESS;
        }

        $this->table(['Tytuł', 'Slug', 'Typ'], $pages->map(fn ($p) => [$p->title, $p->slug, $p->type])->all());

        if (! $this->option('force') && ! $this->confirm('Usunąć '.$pages->count().' przykładowych podstron?', true)) {
            $this->comment('Anulowano.');

            return self::SUCCESS;
        }

        foreach ($pages as $page) {
            $page->delete();
        }

        $this->info($pages->count().' przykładowych podstron usuniętych.');

        return self::SUCCESS;
    }
}
