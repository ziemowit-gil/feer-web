<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\BlockRenderer;
use Illuminate\Database\Seeder;

/**
 * Redakcja stron z myślnikami w roli spójników: „Z Mojej Perspektywy…”, „Dostępność”, „Dostępność komunikacyjna”,
 * „Dostępność cyfrowa”, podstrony „Dostępność w praktyce”, „Szkolenia: prowadzący” i podpis zdjęcia w weCMS.
 * Listy „<strong>Etykieta</strong> — Opis” dostają kropkę po etykiecie, pozostałe zdania są poprawiane pojedynczo.
 * Fakty bez zmian. Strona „Dostępność architektoniczna” (nawiasy szablonowe) nie jest ruszana.
 *
 * Idempotentny: podmienia tylko dokładnie dopasowane fragmenty, więc nie nadpisze zmian z panelu.
 *
 *   php artisan db:seed --class=PagesDashesHumanizedSeeder --force
 */
class PagesDashesHumanizedSeeder extends Seeder
{
    /** Strony z listą „<strong>Etykieta</strong> — Opis” (opis zaczyna się wielką literą). */
    private const LABEL_LIST = [
        'dostepnosc-w-praktyce-zakres',
        'dostepnosc-w-praktyce-zasady',
        'dostepnosc-w-praktyce-dla-kogo',
    ];

    /** @return array<string, array<string, string>> slug => [stary fragment => nowy fragment] */
    private function replacements(): array
    {
        return [
            'z-mojej-perspektywy-przyjdz-i-po-prostu-porozmawiaj' => [
                '&bdquo;Z Mojej Perspektywy&hellip;" &mdash; przyjdź' => '&bdquo;Z Mojej Perspektywy&hellip;": przyjdź',
                'Smoczy Chillout</strong> &mdash; święto' => 'Smoczy Chillout</strong>, święto',
                'To nie kolejna konferencja dla wtajemniczonych &mdash; to wydarzenie' => 'To wydarzenie',
                '&hellip;" &mdash; stoiskiem' => '&hellip;", stoiskiem',
                'materiały informacyjne &mdash; ale po to' => 'materiały informacyjne, ale po to',
                'w turach &mdash; wystarczy' => 'w turach. Wystarczy',
                'się przedstawi &mdash; od tego' => 'się przedstawi, od tego',
                'jesteśmy &mdash; żebyś' => 'jesteśmy, żebyś',
                'rzeczy osobiste &mdash; to przestrzeń' => 'rzeczy osobiste: to przestrzeń',
                'wiedzieć więcej &mdash; to dobry znak' => 'wiedzieć więcej: to dobry znak',
                'Czas się skończy &mdash; to w porządku.' => 'Czas się skończy: to w porządku.',
                'zbyt osobiste &mdash; ma pełne prawo' => 'zbyt osobiste, ma pełne prawo',
                'Smoczy Chillout &mdash; Święta NGO' => 'Smoczy Chillout, Święta NGO',
                'Centrum Obywatelskie &mdash; Reymonta 20.' => 'Centrum Obywatelskie, Reymonta 20.',
            ],
            'dostepnosc' => [
                'dla każdej osoby &mdash; niezależnie' => 'dla każdej osoby, niezależnie',
                'w kontakcie z nami &mdash; poinformuj nas.' => 'w kontakcie z nami, poinformuj nas.',
            ],
            'dostepnosc-komunikacyjna' => [
                'osoby przybranej — dowolnej osoby pełnoletniej' => 'osoby przybranej, czyli dowolnej osoby pełnoletniej',
                'dla Ciebie dostępna — np.' => 'dla Ciebie dostępna, np.',
                'dla Ciebie dostępna &mdash; np.' => 'dla Ciebie dostępna, np.',
            ],
            'dostepnosc-cyfrowa' => [
                'częściowo zgodny</strong> — trwają prace' => 'częściowo zgodny</strong>, trwają prace',
                'częściowo zgodny</strong> &mdash; trwają prace' => 'częściowo zgodny</strong>, trwają prace',
            ],
            'dostepnosc-w-praktyce-zakres' => [
                'narzędzi cyfrowych — od pracy' => 'narzędzi cyfrowych, od pracy',
                'wspierające dostępność — tak, by technologia realnie wspierała misję, a nie ją komplikowała' => 'wspierające dostępność, tak by technologia realnie wspierała misję i jej nie komplikowała',
            ],
            'dostepnosc-w-praktyce-inne-formy' => [
                'w ich działaniach — jeśli są zbieżne' => 'w ich działaniach, jeśli są zbieżne',
                'wsparcie merytoryczne i eksperckie — dostępność' => 'wsparcie merytoryczne i eksperckie: dostępność',
            ],
            'dostepnosc-w-praktyce-osoby-fizyczne' => [
                'osób fizycznych — na rzecz' => 'osób fizycznych, na rzecz',
                'zmieniać otoczenie — często wszystko' => 'zmieniać otoczenie. Często wszystko',
                'naszych możliwości — tak jak w przypadku' => 'naszych możliwości, tak jak w przypadku',
                'na cele zdrowotne — leczenie' => 'na cele zdrowotne: leczenie',
            ],
            'szkolenia-informatyka-prowadzacy' => [
                '<strong>Ziemowit Gil</strong> — tyfloinformatyk' => '<strong>Ziemowit Gil</strong>, tyfloinformatyk',
            ],
            'wecms-funkcje-strony' => [
                'Drzewo stron — przeciąganie' => 'Drzewo stron: przeciąganie',
            ],
        ];
    }

    private function transform(string $text, string $slug, array $map): string
    {
        $text = strtr($text, $map);

        if (in_array($slug, self::LABEL_LIST, true)) {
            $text = preg_replace('~(</strong>) (?:—|&mdash;) (?=[A-ZŻŹĆĄŚĘŁÓŃ])~u', '$1. ', $text) ?? $text;
        }

        return $text;
    }

    public function run(): void
    {
        $slugs = array_unique(array_merge(array_keys($this->replacements()), self::LABEL_LIST));
        $changedPages = 0;

        foreach (Page::withoutGlobalScopes()->whereIn('slug', $slugs)->get() as $page) {
            $map = $this->replacements()[$page->slug] ?? [];
            $changed = false;

            if (is_array($page->blocks) && $page->blocks !== []) {
                $blocks = $this->replaceRecursive($page->blocks, fn ($v) => $this->transform($v, $page->slug, $map), $changed);
                if ($changed) {
                    $blocks = BlockRenderer::normalize($blocks);
                    $page->blocks = $blocks;
                    $page->content = BlockRenderer::render($blocks);
                }
            } else {
                $content = (string) $page->content;
                $new = $this->transform($content, $page->slug, $map);
                if ($new !== $content) {
                    $page->content = $new;
                    $changed = true;
                }
            }

            if ($page->slug === 'z-mojej-perspektywy-przyjdz-i-po-prostu-porozmawiaj' && $page->title === '„Z Mojej Perspektywy…" — przyjdź i po prostu porozmawiaj') {
                $page->title = '„Z Mojej Perspektywy…”: przyjdź i po prostu porozmawiaj';
                $changed = true;
            }

            if ($changed) {
                $page->save();
                $changedPages++;
            }
        }

        $this->command?->info("Strony z myślnikami: zmieniono {$changedPages}.");
    }

    private function replaceRecursive(mixed $value, \Closure $fn, bool &$changed): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->replaceRecursive($v, $fn, $changed);
            }

            return $value;
        }

        if (is_string($value)) {
            $new = $fn($value);
            $changed = $changed || $new !== $value;

            return $new;
        }

        return $value;
    }
}
