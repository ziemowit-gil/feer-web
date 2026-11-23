<?php

namespace App\Support;

use App\Models\NavItem;
use Illuminate\Support\Collection;

/**
 * Mega menu szablonu „kreatywni”: te same pozycje co Menu nawigacji → Menu główne (HeaderNav), a w panelu każdej
 * pozycji jej pierwotne podmenu — podstrony (ręczne podpozycje i podstrony powiązanej strony) oraz sekcje strony,
 * czyli dawny spis „Na tej stronie” (nagłówki H2 jako grupy, H3 jako linki w grupie), z tymi samymi kotwicami,
 * które nadaje App\Support\TableOfContents (widok podstrony templates.kreatywni.page wstrzykuje je do treści).
 * Pozycja bez podmenu zostaje zwykłym linkiem. Pozycja z własnymi podpozycjami w panelu (np. po „Odwzoruj w panelu
 * 1:1”, App\Support\KreatywniMenuSync) pokazuje dokładnie je — nagłówki kolumn jako grupy — bez dobudowywania.
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

            // Własne podpozycje z panelu: dokładnie one, bez podstron i sekcji strony (panel = strona 1:1).
            if ($nav instanceof NavItem && $nav->children->isNotEmpty()) {
                foreach ($nav->megaSections(null, null) as $sec) {
                    $heading = $sec['heading'] ? [
                        'label' => $sec['heading']['label'],
                        'url' => $sec['heading']['url'] ? site_href($sec['heading']['url']) : null,
                        'current' => (bool) ($sec['heading']['current'] ?? false),
                    ] : null;
                    $links = collect($sec['links'])->filter(fn ($l) => filled($l[0]) && $l[0] !== '#')
                        ->map(fn ($l) => ['label' => $l[1], 'url' => site_href($l[0]), 'current' => (bool) $l[4]])->values()->all();
                    if ($heading !== null || $links !== []) {
                        $groups[] = ['heading' => $heading, 'links' => $links];
                    }
                }
                $item->description = $page ? trim((string) $page->meta_description) : '';
                $item->groups = $groups;
                $item->hasPanel = $groups !== [];

                return self::withDocuments($item, $page, $base);
            }

            $children = collect($item->flat)->filter(fn ($l) => filled($l['url']))
                ->map(fn ($l) => ['label' => $l['label'], 'url' => $l['url'], 'current' => (bool) $l['current']])->values()->all();
            if ($children !== []) {
                $groups[] = ['heading' => null, 'links' => $children];
            }

            if ($page && $base && $page->usesStandardLayout() && self::isAboutSlug((string) $page->slug)) {
                // Dedykowana podstrona „O nas”: sekcje z templates.kreatywni.partials.about.
                $texts = KreatywniTexts::for();
                $groups[] = ['heading' => null, 'links' => array_values(array_filter([
                    \App\Models\KreatywniPerson::teamRows($texts, 'about_team') !== [] ? ['label' => $texts->get('about_team_title'), 'url' => $base.'#zespol', 'current' => false] : null,
                    $texts->rows('about_why') !== [] ? ['label' => $texts->get('about_why_title'), 'url' => $base.'#dlaczego', 'current' => false] : null,
                    $texts->get('about_story') !== '' ? ['label' => $texts->get('about_story_title'), 'url' => $base.'#historia', 'current' => false] : null,
                    ['label' => $texts->get('about_join_title'), 'url' => $base.'#dolacz', 'current' => false],
                ]))];
            } elseif ($page && $base && $page->usesStandardLayout() && filled($page->content)) {
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
            if ($page && self::isAboutSlug((string) $page->slug) && $item->description === '') {
                $item->description = KreatywniTexts::for()->get('about_lead');
            }
            $item->groups = $groups;
            $item->hasPanel = $groups !== [];

            return self::withDocuments($item, $page, $base);
        });
    }

    /** Ile dokumentów pokazuje blok „Dokumenty” w menu (reszta pod „Wszystkie dokumenty”). */
    public const MENU_DOCUMENTS = 6;

    /**
     * Pozycja prowadząca do dedykowanej strony „O nas” dostaje w panelu blok „Dokumenty” (moduł „Dokumenty – Kreatywni”,
     * partials/mega-documents): do MENU_DOCUMENTS dokumentów — z plikiem, stroną albo adresem link wprost, „w przygotowaniu”
     * link do sekcji #dokumenty — i „Wszystkie dokumenty”. Działa także przy podpozycjach odwzorowanych w panelu 1:1.
     */
    private static function withDocuments(object $item, ?\App\Models\Page $page, ?string $base): object
    {
        $item->documents = [];
        $item->documentsUrl = null;
        $item->documentsMore = 0;
        if (! $page || ! $base || ! self::isAboutSlug((string) $page->slug)) {
            return $item;
        }
        $docs = \App\Models\KreatywniDocument::forDisplay();
        if ($docs === []) {
            return $item;
        }
        $item->documentsUrl = $base.'#dokumenty';
        $item->documents = array_map(fn (array $d) => $d + ['href' => $d['url'] ?? $item->documentsUrl], array_slice($docs, 0, self::MENU_DOCUMENTS));
        $item->documentsMore = max(0, count($docs) - self::MENU_DOCUMENTS);
        $item->hasPanel = true;

        return $item;
    }

    /** Adres dedykowanej podstrony „O nas” (o-nas, o-nas-2… — UniqueSlug dopisuje numer w sieci witryn). */
    public static function isAboutSlug(string $slug): bool
    {
        return (bool) preg_match('/^o-nas(-\d+)?$/', $slug);
    }
}
