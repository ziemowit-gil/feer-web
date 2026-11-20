<?php

namespace App\Support;

use App\Models\NavItem;
use Illuminate\Support\Collection;

/**
 * Mega menu szablonu „kreatywni”: te same pozycje co Menu nawigacji → Menu główne (HeaderNav), a w panelu każdej
 * pozycji jej pierwotne podmenu — podstrony (ręczne podpozycje i podstrony powiązanej strony) oraz sekcje strony,
 * czyli dawny spis „Na tej stronie” (nagłówki H2 jako grupy, H3 jako linki w grupie), z tymi samymi kotwicami,
 * które nadaje App\Support\TableOfContents (widok podstrony templates.kreatywni.page wstrzykuje je do treści).
 * Pozycja bez podmenu zostaje zwykłym linkiem.
 */
final class KreatywniMegaMenu
{
    /**
     * @param  Collection<int, NavItem>  $navItems  pozycje najwyższego poziomu (z podpozycjami)
     * @return Collection<int, object> HeaderNav::items() + description, groups (heading ?{label,url}, links [{label,url,current}])
     */
    public static function items(Collection $navItems): Collection
    {
        $navItems = $navItems->values();

        return HeaderNav::items($navItems)->values()->map(function (object $item, int $i) use ($navItems) {
            $nav = $navItems[$i] ?? null;
            $page = $nav instanceof NavItem ? $nav->linkedPage() : null;
            $base = $item->hasUrl ? strtok((string) $item->url, '#') : null;

            $groups = [];

            $children = collect($item->flat)->filter(fn ($l) => filled($l['url']))
                ->map(fn ($l) => ['label' => $l['label'], 'url' => $l['url'], 'current' => (bool) $l['current']])->values()->all();
            if ($children !== []) {
                $groups[] = ['heading' => null, 'links' => $children];
            }

            if ($page && $base && $page->usesStandardLayout() && filled($page->content)) {
                [, $toc] = TableOfContents::inject((string) $page->content, 1);
                $current = null;
                foreach ($toc as $entry) {
                    $link = ['label' => $entry['text'], 'url' => $base.'#'.$entry['id'], 'current' => false];
                    if ($entry['level'] === 2) {
                        if ($current !== null) {
                            $groups[] = $current;
                        }
                        $current = ['heading' => $link, 'links' => []];
                    } else {
                        $current ??= ['heading' => null, 'links' => []];
                        $current['links'][] = $link;
                    }
                }
                if ($current !== null) {
                    $groups[] = $current;
                }
            }

            $item->description = $page ? trim((string) $page->meta_description) : '';
            $item->groups = $groups;
            $item->hasPanel = $groups !== [];

            return $item;
        });
    }
}
