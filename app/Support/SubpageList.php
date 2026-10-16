<?php

namespace App\Support;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * Lista linków do podstron wstawiana w treść — shortcode `[podstrony]`.
 *
 * Wspólny mechanizm obu edytorów: blok „Lista podstron" edytora blokowego
 * zapisuje w `content` ten sam shortcode, który wstawia WYSIWYG, a lista
 * składa się dopiero przy renderowaniu strony (ShortcodeParser) — nowa
 * opublikowana podstrona pojawia się sama, bez ponownego zapisu treści.
 *
 * Składnia (wszystkie parametry opcjonalne, rozdzielone „;"):
 *   [podstrony]                                   — dzieci bieżącej strony, kolejność z drzewa, prosta lista
 *   [podstrony:strona=12;sortowanie=alfabet;poziomy=2;wyglad=kafle]
 *   [podstrony:wybrane=4,9,7;wyglad=zajawki]      — ręcznie wybrane strony (w podanej kolejności)
 *
 *   strona      ID strony, której podstrony pokazać (domyślnie bieżąca strona)
 *   wybrane     ID stron wybranych ręcznie — zastępują podstrony źródła
 *   sortowanie  drzewo (kolejność z drzewa stron) | alfabet
 *   poziomy     1 (tylko dzieci) | 2 (dzieci i wnuki)
 *   wyglad      lista | zajawki | kafle (kafle w 2 kolumnach)
 *
 * Pomijane są strony nieopublikowane (także z publikacją w przyszłości),
 * wyłączone (same lub przez stronę nadrzędną), ukryte na mapie strony,
 * strony wewnętrzne z kontrolą dostępu oraz strony osób i nagłówki menu.
 */
class SubpageList
{
    public const SORTS = [
        'drzewo' => 'Kolejność z drzewa stron',
        'alfabet' => 'Alfabetycznie',
    ];

    public const DEPTHS = [
        1 => 'Tylko podstrony (dzieci)',
        2 => 'Podstrony i ich podstrony (dzieci i wnuki)',
    ];

    public const LAYOUTS = [
        'lista' => 'Prosta lista linków',
        'zajawki' => 'Lista z zajawką',
        'kafle' => 'Kafle w 2 kolumnach',
    ];

    /** Maksymalna liczba ręcznie wybranych stron. */
    public const MAX_PICKED = 60;

    /** Typy stron, które nie trafiają na listę (osoby, nagłówki menu, strony wewnętrzne). */
    private const SKIPPED_TYPES = ['about_person', 'menu_heading', 'internal', 'internal_hub', 'brand_assets'];

    /** Bieżąca strona — źródło domyślne; ustawia ją PageController przed renderowaniem. */
    private static ?Page $contextPage = null;

    public static function setContextPage(?Page $page): void
    {
        static::$contextPage = $page;
    }

    public static function contextPage(): ?Page
    {
        if (static::$contextPage) {
            return static::$contextPage;
        }
        $routePage = request()->route()?->parameter('page');

        return $routePage instanceof Page ? $routePage : null;
    }

    /**
     * Opcje z parametrów shortcode'u („strona=12;wyglad=kafle").
     *
     * @return array{page: ?int, picked: array<int, int>, sort: string, depth: int, layout: string}
     */
    public static function parse(?string $args): array
    {
        $raw = [];
        foreach (preg_split('/[;|]/', html_entity_decode((string) $args, ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $pair) {
            if (str_contains($pair, '=')) {
                [$k, $v] = array_map('trim', explode('=', $pair, 2));
                $raw[mb_strtolower($k)] = $v;
            }
        }

        return static::normalize([
            'page' => $raw['strona'] ?? null,
            'picked' => isset($raw['wybrane']) ? explode(',', $raw['wybrane']) : [],
            'sort' => $raw['sortowanie'] ?? null,
            'depth' => $raw['poziomy'] ?? null,
            'layout' => $raw['wyglad'] ?? null,
        ]);
    }

    /**
     * Opcje z dowolnego wejścia (atrybuty bloku, parametry shortcode'u) — tylko dozwolone wartości.
     *
     * @return array{page: ?int, picked: array<int, int>, sort: string, depth: int, layout: string}
     */
    public static function normalize(array $a): array
    {
        $page = (int) ($a['page'] ?? 0);
        $picked = array_values(array_unique(array_filter(array_map('intval', is_array($a['picked'] ?? null) ? $a['picked'] : []), fn ($id) => $id > 0)));

        return [
            'page' => $page > 0 ? $page : null,
            'picked' => array_slice($picked, 0, self::MAX_PICKED),
            'sort' => array_key_exists((string) ($a['sort'] ?? ''), self::SORTS) ? (string) $a['sort'] : 'drzewo',
            'depth' => (int) ($a['depth'] ?? 1) === 2 ? 2 : 1,
            'layout' => array_key_exists((string) ($a['layout'] ?? ''), self::LAYOUTS) ? (string) $a['layout'] : 'lista',
        ];
    }

    /** Shortcode z opcji — domyślne wartości są pomijane, więc `[podstrony]` to najprostsza lista. */
    public static function shortcode(array $options): string
    {
        $o = static::normalize($options);
        $parts = [];
        if ($o['picked'] !== []) {
            $parts[] = 'wybrane='.implode(',', $o['picked']);
        } elseif ($o['page']) {
            $parts[] = 'strona='.$o['page'];
        }
        if ($o['sort'] !== 'drzewo') {
            $parts[] = 'sortowanie='.$o['sort'];
        }
        if ($o['depth'] !== 1) {
            $parts[] = 'poziomy='.$o['depth'];
        }
        if ($o['layout'] !== 'lista') {
            $parts[] = 'wyglad='.$o['layout'];
        }

        return '[podstrony'.($parts ? ':'.implode(';', $parts) : '').']';
    }

    /**
     * Pozycje listy: ['page' => Page, 'children' => Collection<Page>] (dzieci tylko przy 2 poziomach).
     *
     * @return Collection<int, array{page: Page, children: Collection<int, Page>}>
     */
    public static function items(array $options, ?Page $context = null): Collection
    {
        $o = static::normalize($options);
        $context ??= static::contextPage();

        if ($o['picked'] !== []) {
            $order = array_flip($o['picked']);
            $top = static::visible(static::query()->whereIn('id', $o['picked'])->get())
                ->reject(fn (Page $p) => $p->isDisabled())->values();
            $top = $o['sort'] === 'alfabet'
                ? static::sortAlpha($top)
                : $top->sortBy(fn (Page $p) => $order[$p->id] ?? PHP_INT_MAX)->values();
        } else {
            $source = $o['page'] ? static::query()->find($o['page']) : $context;
            if (! $source || ($o['page'] && (! static::isListable($source, true) || $source->isDisabled()))) {
                return collect();
            }
            $top = static::childrenOf(collect([$source->id]), $o['sort'])->get($source->id, collect());
        }

        $grand = $o['depth'] === 2 && $top->isNotEmpty()
            ? static::childrenOf($top->pluck('id'), $o['sort'])
            : collect();

        return $top->map(fn (Page $p) => ['page' => $p, 'children' => $grand->get($p->id, collect())])->values();
    }

    /** Wyrenderowana lista (pusty ciąg, gdy nie ma czego pokazać). */
    public static function render(array $options, ?Page $context = null): string
    {
        $o = static::normalize($options);
        $context ??= static::contextPage();
        $items = static::items($o, $context);
        if ($items->isEmpty()) {
            return '';
        }

        $source = $o['picked'] === [] ? ($o['page'] ? static::query()->find($o['page']) : $context) : null;
        $label = $source ? 'Podstrony: '.$source->title : 'Powiązane strony';

        return View::make('partials.content-link-list', [
            'items' => $items->map(fn (array $item) => [
                'title' => $item['page']->title,
                'url' => $item['page']->navUrl(),
                'excerpt' => $o['layout'] === 'lista' ? null : static::excerpt($item['page']),
                'date' => null,
                'children' => $item['children']->map(fn (Page $c) => ['title' => $c->title, 'url' => $c->navUrl()])->all(),
            ])->all(),
            'layout' => $o['layout'],
            'label' => $label,
        ])->render();
    }

    /** Zajawka: opis dla wyszukiwarek, a gdy go brak — początek treści. */
    public static function excerpt(Page $page, int $limit = 160): string
    {
        $text = trim((string) $page->meta_description);

        return $text !== '' ? \Illuminate\Support\Str::limit($text, $limit) : meta_excerpt($page->content, $limit);
    }

    /**
     * Strony do wyboru w edytorach (źródło i wybór ręczny): opublikowane strony
     * bieżącej witryny w kolejności drzewa, z poziomem zagłębienia.
     *
     * @return array<int, array{id: int, title: string, depth: int, parent: ?int}>
     */
    public static function pickerOptions(): array
    {
        $pages = static::visible(static::query()->get(['id', 'parent_id', 'title', 'type', 'order', 'is_published', 'publish_at', 'is_disabled', 'hide_in_sitemap']), true);
        $byParent = $pages->groupBy(fn (Page $p) => (int) $p->parent_id);
        $ids = $pages->pluck('id')->flip();
        $out = [];
        $seen = [];
        $walk = function (int $parent, int $depth) use (&$walk, &$out, &$seen, $byParent) {
            foreach ($byParent->get($parent, collect())->sortBy([['order', 'asc'], ['title', 'asc']]) as $p) {
                if (isset($seen[$p->id]) || $depth > 10) {
                    continue;
                }
                $seen[$p->id] = true;
                $out[] = ['id' => $p->id, 'title' => (string) $p->title, 'depth' => $depth, 'parent' => $p->parent_id ? (int) $p->parent_id : null];
                $walk($p->id, $depth + 1);
            }
        };
        $walk(0, 0);
        // Strony, których rodzic nie jest na liście (np. szkic) — na końcu, bez wcięcia.
        foreach ($pages as $p) {
            if (! isset($seen[$p->id]) && ! $ids->has((int) $p->parent_id) && $p->parent_id) {
                $out[] = ['id' => $p->id, 'title' => (string) $p->title, 'depth' => 0, 'parent' => (int) $p->parent_id];
                $seen[$p->id] = true;
                $walk($p->id, 1);
            }
        }

        return $out;
    }

    private static function query()
    {
        return Page::query()->withoutGlobalScope('editor_scope')->forCurrentSite()->live();
    }

    /** @return Collection<int, Collection<int, Page>> dzieci pogrupowane po parent_id */
    private static function childrenOf(Collection $parentIds, string $sort): Collection
    {
        $children = static::visible(static::query()->whereIn('parent_id', $parentIds->all())->orderBy('order')->orderBy('title')->get());

        return $children->groupBy('parent_id')->map(fn (Collection $group) => $sort === 'alfabet' ? static::sortAlpha($group) : $group->values());
    }

    private static function sortAlpha(Collection $pages): Collection
    {
        $collator = class_exists(\Collator::class) ? new \Collator('pl_PL') : null;

        return $pages->sort(fn (Page $a, Page $b) => $collator
            ? $collator->compare((string) $a->title, (string) $b->title)
            : strcasecmp((string) $a->title, (string) $b->title))->values();
    }

    private static function visible(Collection $pages, bool $allowSkippedTypes = false): Collection
    {
        return $pages->filter(fn (Page $p) => static::isListable($p, $allowSkippedTypes))->values();
    }

    private static function isListable(Page $page, bool $allowSkippedTypes = false): bool
    {
        if (! $page->is_published || ($page->publish_at && $page->publish_at->isFuture())) {
            return false;
        }
        // Wyłączenie przez stronę nadrzędną sprawdzają wywołujący (dzieci źródła dziedziczą jego stan).
        if ($page->is_disabled || $page->hide_in_sitemap) {
            return false;
        }

        return $allowSkippedTypes || ! in_array($page->type, self::SKIPPED_TYPES, true);
    }
}
