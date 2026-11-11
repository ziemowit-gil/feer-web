<?php

namespace App\Support;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Drzewo menu szablonu „bu2” — jedno źródło dla mega menu w nagłówku, menu bocznego, okruszków i kafli podstron.
 *
 * Źródło: Menu nawigacji (pozycje „main”) z ręcznymi podpozycjami (pozycja „nagłówek kolumny” zbiera kolejne
 * podpozycje pod sobą), a pod pozycją prowadzącą do strony — jej opublikowane podstrony, rekurencyjnie (do 5 poziomów).
 * Strony czytamy jednym zapytaniem (bez treści), więc głębokie drzewo nie dokłada zapytań.
 *
 * Węzeł: id, label, url (null = sam napis), path (ścieżka do porównań), icon, current (to ta strona), open (gałąź
 * bieżącej strony), children. Bieżący węzeł: adres żądania, potem strony nadrzędne bieżącej strony, a na końcu
 * najdłuższy adres z menu, którym zaczyna się adres żądania (np. wpis pod /aktualnosci). Liczone raz na żądanie.
 */
class Bu2Menu
{
    private const MAX_DEPTH = 5;

    private const KEY = 'bu2.menu';

    /** @return array<int, array<string, mixed>> */
    public static function tree(): array
    {
        return self::state()['tree'];
    }

    /**
     * Ścieżka od najwyższego poziomu do bieżącej strony ([label, url], ostatni = bieżąca strona) — tylko gdy bieżąca
     * strona jest w menu; w przeciwnym razie pusta (okruszki biorą wtedy ścieżkę z widoku).
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    public static function trail(): array
    {
        $state = self::state();

        return $state['exact'] ? $state['trail'] : [];
    }

    /** Węzeł bieżącej strony, gdy jest w menu (do kafli podstron). @return array<string, mixed>|null */
    public static function currentNode(): ?array
    {
        $state = self::state();

        return $state['exact'] ? $state['node'] : null;
    }

    /** Węzeł, pod którym jest bieżąca strona (ona sama, strona nadrzędna albo dział z menu). @return array<string, mixed>|null */
    public static function anchorNode(): ?array
    {
        return self::state()['node'];
    }

    /** Ścieżka adresu bez ukośników na brzegach (do porównań); null dla linków zewnętrznych, kotwic i pustych. */
    public static function pathOf(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === '#' || Str::startsWith($url, ['#', 'mailto:', 'tel:', 'javascript:'])) {
            return null;
        }
        if (Str::startsWith($url, ['http://', 'https://', '//'])) {
            $root = rtrim(url('/'), '/');
            if (! Str::startsWith($url, $root)) {
                return null;
            }
            $url = Str::after($url, $root);
        }

        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    /** Czyści zapamiętane drzewo bieżącego żądania (testy). */
    public static function forget(): void
    {
        request()->attributes->remove(self::KEY);
    }

    /** @return array{tree: array, trail: array, node: ?array, exact: bool} */
    private static function state(): array
    {
        $attrs = request()->attributes;
        if ($attrs->has(self::KEY)) {
            return $attrs->get(self::KEY);
        }

        try {
            $state = self::build();
        } catch (\Throwable $e) {
            report($e);
            $state = ['tree' => [], 'trail' => [], 'node' => null, 'exact' => false];
        }
        $attrs->set(self::KEY, $state);

        return $state;
    }

    private static function build(): array
    {
        $settings = SiteSetting::current();

        $pages = Page::forCurrentSite()->where('is_published', true)->where('type', '!=', 'about_person')
            ->orderBy('order')->orderBy('title')
            ->get(['id', 'site_id', 'parent_id', 'project_id', 'title', 'slug', 'type', 'order', 'layout_data', 'is_published']);
        $byParent = $pages->groupBy(fn (Page $p) => (int) $p->parent_id);
        $bySlug = $pages->keyBy('slug');

        $items = NavItem::forCurrentSite()->where('location', 'main')->whereNull('parent_id')->where('is_active', true)
            ->with('children.children.children')->orderBy('order')->get()
            ->filter(fn (NavItem $item) => ! $item->module || $settings->isModuleEnabled($item->module))
            ->values();

        $seq = 0;
        $ctx = ['byParent' => $byParent, 'bySlug' => $bySlug, 'seq' => &$seq, 'topPages' => $pages->whereNull('parent_id')->whereNull('project_id')];
        $tree = $items->map(fn (NavItem $item) => self::fromNav($item, 1, $ctx))->filter()->values()->all();

        [$nodeId, $exact] = self::findCurrent($tree, $pages, $ctx);
        $trail = [];
        $node = null;
        $tree = self::mark($tree, $nodeId, $trail, $node);

        return ['tree' => $tree, 'trail' => $trail, 'node' => $node, 'exact' => $exact];
    }

    private static function fromNav(NavItem $item, int $depth, array &$ctx): ?array
    {
        $url = filled($item->url) && $item->url !== '#' ? site_href($item->url) : self::moduleUrl($item->type);
        $children = [];

        if ($depth < self::MAX_DEPTH) {
            if ($item->type === 'pages') {
                // „Menu podstron”: strony najwyższego poziomu (bez działań), jak w zwykłym nagłówku.
                foreach ($ctx['topPages'] as $p) {
                    $children[] = self::fromPage($p, $depth + 1, $ctx);
                }
            } elseif (! in_array($item->type, NavItem::MODULE_TYPES, true)) {
                $group = null;
                foreach ($item->children as $child) {
                    if ($child->is_column_heading) {
                        if ($group) {
                            $children[] = $group;
                        }
                        $group = self::fromNav($child, $depth + 1, $ctx);

                        continue;
                    }
                    $node = self::fromNav($child, $depth + 1, $ctx);
                    if ($group) {
                        $group['children'][] = $node;
                    } else {
                        $children[] = $node;
                    }
                }
                if ($group) {
                    $children[] = $group;
                }

                // Pozycja prowadząca do strony: jej podstrony (bez powtórzeń tego, co już jest w menu).
                $page = self::pageFor($url, $ctx);
                if ($page) {
                    $known = collect($children)->pluck('path')->filter()->all();
                    foreach ($ctx['byParent']->get($page->id, collect()) as $p) {
                        $n = self::fromPage($p, $depth + 1, $ctx);
                        if (! in_array($n['path'], $known, true)) {
                            $children[] = $n;
                        }
                    }
                }
            }
        }

        $icon = filled($item->icon) ? (string) $item->icon : null;
        if (! $icon && ($page ??= self::pageFor($url, $ctx))) {
            $icon = self::pageIcon($page);
        }

        return self::node((string) $item->label, $url, $icon, $children, $ctx);
    }

    private static function fromPage(Page $page, int $depth, array &$ctx): array
    {
        $children = [];
        if ($depth < self::MAX_DEPTH) {
            foreach ($ctx['byParent']->get($page->id, collect()) as $p) {
                $children[] = self::fromPage($p, $depth + 1, $ctx);
            }
        }

        return self::node((string) $page->title, self::pageUrl($page, $ctx), self::pageIcon($page), $children, $ctx);
    }

    private static function node(string $label, ?string $url, ?string $icon, array $children, array &$ctx): array
    {
        $ctx['seq']++;

        return [
            'id' => 'n'.$ctx['seq'],
            'label' => $label,
            'url' => $url,
            'path' => self::pathOf($url),
            'icon' => $icon,
            'current' => false,
            'open' => false,
            'children' => $children,
        ];
    }

    /** Strona z adresu pozycji menu (adres jednoczłonowy /{slug} albo ostatni człon ścieżki witryny). */
    private static function pageFor(?string $url, array &$ctx): ?Page
    {
        $path = self::pathOf($url);
        if ($path === null || $path === '') {
            return null;
        }
        $page = $ctx['bySlug']->get(Str::afterLast($path, '/'));

        return $page && self::pathOf(self::pageUrl($page, $ctx)) === $path ? $page : null;
    }

    /**
     * Adres strony bez zapytania na każdą stronę: Page::publicUrl() szuka witryny strony w bazie, więc liczymy go raz
     * (dla pierwszej strony) i podmieniamy końcowy slug — trasa page.show kończy się na /{slug}.
     */
    private static function pageUrl(Page $page, array &$ctx): string
    {
        if (! isset($ctx['urlBase'])) {
            $url = $page->publicUrl();
            $suffix = '/'.rawurlencode((string) $page->slug);
            $ctx['urlBase'] = str_ends_with($url, $suffix) ? substr($url, 0, -strlen($suffix)) : false;
            if ($ctx['urlBase'] === false) {
                return $url;
            }
        }

        return $ctx['urlBase'] === false ? $page->publicUrl() : $ctx['urlBase'].'/'.rawurlencode((string) $page->slug);
    }

    private static function pageIcon(Page $page): ?string
    {
        $icon = trim((string) (($page->layout_data ?? [])['icon'] ?? ''));

        return $icon !== '' ? $icon : null;
    }

    private static function moduleUrl(?string $type): ?string
    {
        $route = match ($type) {
            'projects' => 'projects.index',
            'events' => 'events.index',
            'volunteering' => 'volunteering.index',
            'faq' => 'faq.index',
            default => null,
        };

        return $route && \Illuminate\Support\Facades\Route::has($route) ? site_route($route) : null;
    }

    /** @return array{0: ?string, 1: bool} id bieżącego węzła i czy to dokładnie ta strona */
    private static function findCurrent(array $tree, Collection $pages, array &$ctx): array
    {
        $flat = [];
        $walk = function (array $nodes) use (&$walk, &$flat) {
            foreach ($nodes as $n) {
                if ($n['path'] !== null) {
                    $flat[] = $n;
                }
                $walk($n['children']);
            }
        };
        $walk($tree);

        $here = trim(request()->path(), '/');
        $hit = collect($flat)->first(fn ($n) => $n['path'] === $here);
        if ($hit) {
            return [$hit['id'], true];
        }

        // Strona spoza menu: najbliższa strona nadrzędna, która w nim jest.
        $route = request()->route('page');
        $current = $route instanceof Page ? $route : ($route ? $pages->firstWhere('slug', (string) $route) : null);
        $byId = $pages->keyBy('id');
        for ($p = $current ? $byId->get($current->parent_id) : null, $guard = 0; $p && $guard < 10; $p = $byId->get($p->parent_id), $guard++) {
            $path = self::pathOf(self::pageUrl($p, $ctx));
            $hit = collect($flat)->first(fn ($n) => $n['path'] === $path);
            if ($hit) {
                return [$hit['id'], false];
            }
        }

        // Adres pod działem z menu (np. /aktualnosci/wpis): najdłuższy pasujący początek.
        $hit = collect($flat)->filter(fn ($n) => $n['path'] !== '' && str_starts_with($here.'/', $n['path'].'/'))
            ->sortByDesc(fn ($n) => strlen($n['path']))->first();

        return [$hit['id'] ?? null, false];
    }

    /** Ustawia current/open na ścieżce do węzła i zbiera okruszki. */
    private static function mark(array $nodes, ?string $id, array &$trail, ?array &$found): array
    {
        if ($id === null) {
            return $nodes;
        }
        foreach ($nodes as $i => $n) {
            if ($n['id'] === $id) {
                $n['current'] = true;
                $n['open'] = true;
                $trail[] = ['label' => $n['label'], 'url' => $n['url']];
                $found = $n;
                $nodes[$i] = $n;

                return $nodes;
            }
            $sub = [];
            $n['children'] = self::mark($n['children'], $id, $sub, $found);
            if ($sub !== []) {
                $n['open'] = true;
                $trail = array_merge([['label' => $n['label'], 'url' => $n['url']]], $sub);
                $nodes[$i] = $n;

                return $nodes;
            }
        }

        return $nodes;
    }
}
