<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Page;
use Illuminate\Console\Command;

/**
 * Usuwa przykładowe podstrony dodane przez FederationPageTypesDemoSeeder
 * (wszystkie sluggi zaczynające się od "demo-") oraz organizacje oznaczone
 * jako testowe — do uruchomienia przed przekazaniem instalacji do produkcji.
 */
class FederationCleanDemoPages extends Command
{
    protected $signature = 'federation:clean-demo {--force : Usuń bez pytania o potwierdzenie}';

    protected $description = 'Usuwa przykładowe podstrony (sluggi demo-*) oraz testowe organizacje członkowskie';

    public function handle(): int
    {
        $pages = Page::where('slug', 'like', 'demo-%')->get();
        $organizations = Organization::where('is_test', true)->get();

        if ($pages->isEmpty() && $organizations->isEmpty()) {
            $this->info('Brak przykładowych podstron ani testowych organizacji do usunięcia.');

            return self::SUCCESS;
        }

        if ($pages->isNotEmpty()) {
            $this->table(['Tytuł', 'Slug', 'Typ'], $pages->map(fn ($p) => [$p->title, $p->slug, $p->type])->all());
        }

        if ($organizations->isNotEmpty()) {
            $this->table(['Organizacja testowa', 'Login'], $organizations->map(fn ($o) => [$o->name, $o->login])->all());
        }

        $total = $pages->count() + $organizations->count();

        if (! $this->option('force') && ! $this->confirm("Usunąć {$total} przykładowych pozycji (podstrony + organizacje testowe)?", true)) {
            $this->comment('Anulowano.');

            return self::SUCCESS;
        }

        foreach ($pages as $page) {
            $page->delete();
        }

        foreach ($organizations as $organization) {
            $organization->delete();
        }

        $this->info($total.' przykładowych pozycji usuniętych.');

        return self::SUCCESS;
    }
}
