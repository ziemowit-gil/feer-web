<?php

namespace App\Support;

use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Gdzie w menu nawigacji jest strona: pozycje menu (główne, stopka, BIP), których adres prowadzi do strony, oraz
 * strony dopisywane automatycznie przez pozycję „Menu podstron” (`pages.show_in_menu` stron najwyższego poziomu). Liczone hurtem dla całego drzewa
 * (jedno zapytanie o pozycje menu), bo drzewo stron pokazuje znacznik przy każdej stronie.
 *
 * Dopasowanie po ścieżce adresu, jak w mapie zależności (PageGraph): „/slug” oraz ścieżka publicznego adresu strony.
 */
final class PageMenuPositions
{
    /**
     * @param  Collection<int, Page>  $pages
     * @return array<int, array<int, array{item: ?NavItem, location: string, location_label: string, parent: ?string, label: string, active: bool, auto: bool}>>
     */
    public static function forPages(Collection $pages): array
    {
        $byPath = [];
        foreach ($pages as $page) {
            foreach (array_filter(['/'.$page->slug, self::path($page->publicUrl())]) as $path) {
                $byPath[$path] ??= $page->id;
            }
        }

        $positions = [];
        $items = NavItem::query()->forCurrentSite()->with('parent:id,label')
            ->orderByRaw('parent_id is not null')->orderBy('order')->get();
        foreach ($items as $item) {
            if (blank($item->url) || str_starts_with((string) $item->url, '#')) {
                continue;
            }
            $path = self::path((string) $item->url);
            if ($path === null || ! isset($byPath[$path])) {
                continue;
            }
            $positions[$byPath[$path]][] = [
                'item' => $item,
                'location' => (string) $item->location,
                'location_label' => self::locationLabel((string) $item->location),
                'parent' => $item->parent?->label,
                'label' => (string) $item->label,
                'active' => (bool) ($item->is_active ?? true),
                'auto' => false,
            ];
        }

        // „Menu podstron” (pozycja typu `pages`): rozwija strony najwyższego poziomu z włączonym „Pokaż w menu” —
        // tylko wtedy to ustawienie strony coś znaczy (domyślnie jest włączone dla każdej strony).
        foreach ($items->where('type', 'pages') as $pagesItem) {
            foreach ($pages as $page) {
                if ($page->show_in_menu && ! $page->parent_id && ! $page->project_id && $page->is_published) {
                    $positions[$page->id][] = [
                        'item' => null, 'location' => (string) $pagesItem->location,
                        'location_label' => self::locationLabel((string) $pagesItem->location),
                        'parent' => (string) $pagesItem->label, 'label' => $page->title,
                        'active' => (bool) ($pagesItem->is_active ?? true), 'auto' => true,
                    ];
                }
            }
        }

        // Kolejność: menu główne, stopka, BIP; w obrębie miejsca jak w menu.
        $rank = ['main' => 0, 'footer' => 1, 'bip' => 2];
        foreach ($positions as &$list) {
            usort($list, fn ($a, $b) => ($rank[$a['location']] ?? 9) <=> ($rank[$b['location']] ?? 9));
        }
        unset($list);

        return $positions;
    }

    /** Krótka nazwa miejsca menu: „Menu główne”, „Stopka”, „Menu BIP”. */
    public static function locationLabel(string $location): string
    {
        return match ($location) {
            'main' => 'Menu główne',
            'footer' => 'Stopka',
            'bip' => 'Menu BIP',
            default => NavItem::LOCATIONS[$location] ?? $location,
        };
    }

    /** Ścieżka adresu w serwisie (bez domeny serwisu, bez końcowego „/”) albo null dla adresów zewnętrznych. */
    private static function path(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $url)) {
            $base = rtrim(url('/'), '/');
            if (! str_starts_with($url, $base)) {
                return null;
            }
            $url = substr($url, strlen($base));
        }
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path === '' || $path === '/' ? null : '/'.trim($path, '/');
    }
}
