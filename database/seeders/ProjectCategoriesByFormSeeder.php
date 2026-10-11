<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Nowy podział działań w mega menu: według formy działania zamiast odbiorcy
 * („Dla każdego”, „Dla NGO”… miały po jednym działaniu).
 *
 *  - Szkolenia i warsztaty — szkolenia, akademie, warsztaty,
 *  - Edukacja i materiały — edukacja, materiały edukacyjne,
 *  - Wsparcie dla organizacji — wsparcie (technologiczne itp.).
 *
 * Przypisuje działania najwyższego poziomu po słowach w tytule; działań, które
 * nie pasują do żadnej grupy, nie rusza. Dawne kategorie zostają (puste znikają
 * z mega menu same). Idempotentny — można uruchamiać wielokrotnie.
 *
 * Uruchomienie: php artisan db:seed --class=ProjectCategoriesByFormSeeder --force
 */
class ProjectCategoriesByFormSeeder extends Seeder
{
    /** Kategoria → słowa w tytule (małe litery, kolejność ma znaczenie: pierwsze dopasowanie wygrywa). */
    public const GROUPS = [
        'Szkolenia i warsztaty' => ['szkoleni', 'akademi', 'warsztat', 'kurs'],
        'Edukacja i materiały' => ['edukacj', 'materiał', 'poradnik'],
        'Wsparcie dla organizacji' => ['wsparci', 'pomoc', 'doradztw'],
    ];

    public function run(): void
    {
        $categories = [];
        $order = 1;
        foreach (array_keys(self::GROUPS) as $name) {
            $categories[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'order' => $order]);
            $categories[$name]->update(['order' => $order++]);
        }

        $moved = [];
        Project::query()->whereNull('parent_id')->get()->each(function (Project $project) use ($categories, &$moved) {
            $title = mb_strtolower($project->title);

            foreach (self::GROUPS as $name => $words) {
                if (collect($words)->contains(fn ($w) => str_contains($title, $w))) {
                    if ($project->category_id !== $categories[$name]->id) {
                        $project->forceFill(['category_id' => $categories[$name]->id])->saveQuietly();
                    }
                    $moved[] = "{$project->title} → {$name}";

                    return;
                }
            }
        });

        // Dawne kategorie bez działań na koniec listy (w mega menu i tak są ukryte).
        Category::whereNotIn('id', collect($categories)->pluck('id'))->whereDoesntHave('projects')
            ->get()->each(fn (Category $c) => $c->update(['order' => 100 + $c->id]));

        foreach ($moved as $line) {
            $this->command?->line($line);
        }
    }
}
