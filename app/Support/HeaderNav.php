<?php

namespace App\Support;

use App\Models\NavItem;
use Illuminate\Support\Collection;

/**
 * Menu główne w postaci prostej do wyrenderowania w nagłówkach szablonów (test2, test3): dla każdej pozycji adres,
 * bieżący stan, kolor akcentu, a dla pozycji z podmenu (ręczne podpozycje i podstrony powiązanej strony) sekcje i płaska
 * lista linków oraz informacja, czy pozycja jest „mega” (kolumny z nagłówkami).
 *
 * Wpis linku w `flat` i w `sections[].links`: url, label, description, current.
 */
class HeaderNav
{
    /**
     * @param  Collection<int, NavItem>  $navItems  pozycje najwyższego poziomu (z załadowanymi podpozycjami)
     * @return Collection<int, object>
     */
    public static function items(Collection $navItems): Collection
    {
        $route = request()->route('page');
        $currentPageId = request()->routeIs('page.show') && is_object($route) ? $route->id : null;

        return $navItems->map(function (NavItem $item) use ($currentPageId) {
            $sections = collect($item->megaSections($currentPageId))->map(fn ($sec) => [
                'heading' => $sec['heading'] ? [
                    'label' => $sec['heading']['label'],
                    'url' => $sec['heading']['url'] ? site_href($sec['heading']['url']) : null,
                    'current' => (bool) ($sec['heading']['current'] ?? false),
                ] : null,
                'links' => collect($sec['links'])->map(fn ($l) => [
                    'url' => site_href($l[0]), 'label' => $l[1], 'description' => $l[2], 'current' => (bool) $l[4],
                ])->all(),
            ])->all();

            $grouped = NavItem::sectionsAreGrouped($sections);
            $flat = collect($sections)->flatMap(fn ($sec) => array_merge(
                $sec['heading'] ? [['url' => $sec['heading']['url'], 'label' => $sec['heading']['label'], 'description' => null, 'current' => $sec['heading']['current']]] : [],
                $sec['links']
            ))->values()->all();

            return (object) [
                'label' => $item->label,
                'url' => site_href($item->url),
                'hasUrl' => filled($item->url) && $item->url !== '#',
                'current' => $item->isCurrent(),
                'accent' => Color::isValid($item->accent_color) ? $item->accent_color : null,
                'mega' => $item->isMega() && $sections !== [],
                'sections' => $sections,
                'grouped' => $grouped,
                'flat' => $flat,
                'cols' => $grouped ? min(4, max(2, count($sections))) : max(2, min(4, (int) ceil(count($flat) / 4))),
            ];
        });
    }
}
