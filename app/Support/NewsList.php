<?php

namespace App\Support;

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/**
 * Lista linków do aktualności wstawiana w treść — shortcode `[aktualnosci]`.
 *
 * Działa jak lista podstron (App\Support\SubpageList): blok „Lista aktualności"
 * edytora blokowego zapisuje w `content` ten sam shortcode, który wstawia
 * WYSIWYG, a wpisy dobierane są przy renderowaniu — nowa aktualność pojawia
 * się sama.
 *
 * Składnia (parametry opcjonalne, rozdzielone „;"):
 *   [aktualnosci]                                        — 5 najnowszych, prosta lista
 *   [aktualnosci:kategoria=3;liczba=6;sortowanie=alfabet;wyglad=kafle]
 *
 *   kategoria   ID kategorii aktualności (domyślnie wszystkie)
 *   liczba      ile wpisów (1–30, domyślnie 5)
 *   sortowanie  najnowsze | alfabet
 *   wyglad      lista | zajawki | kafle (kafle w 2 kolumnach)
 *
 * Pokazywane są tylko opublikowane wpisy bieżącej witryny (bez szkiców,
 * zaplanowanych, zarchiwizowanych i wygasłych komunikatów — News::published()).
 * Aktualność, w której treści stoi lista, nie linkuje do samej siebie.
 */
class NewsList
{
    public const SORTS = [
        'najnowsze' => 'Od najnowszych',
        'alfabet' => 'Alfabetycznie',
    ];

    public const LAYOUTS = SubpageList::LAYOUTS;

    public const DEFAULT_LIMIT = 5;

    public const MAX_LIMIT = 30;

    /**
     * @return array{category: ?int, limit: int, sort: string, layout: string}
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
            'category' => $raw['kategoria'] ?? null,
            'limit' => $raw['liczba'] ?? null,
            'sort' => $raw['sortowanie'] ?? null,
            'layout' => $raw['wyglad'] ?? null,
        ]);
    }

    /**
     * @return array{category: ?int, limit: int, sort: string, layout: string}
     */
    public static function normalize(array $a): array
    {
        $category = (int) ($a['category'] ?? 0);
        $limit = (int) ($a['limit'] ?? 0);

        return [
            'category' => $category > 0 ? $category : null,
            'limit' => $limit > 0 ? min(self::MAX_LIMIT, $limit) : self::DEFAULT_LIMIT,
            'sort' => array_key_exists((string) ($a['sort'] ?? ''), self::SORTS) ? (string) $a['sort'] : 'najnowsze',
            'layout' => array_key_exists((string) ($a['layout'] ?? ''), self::LAYOUTS) ? (string) $a['layout'] : 'lista',
        ];
    }

    /** Shortcode z opcji — domyślne wartości są pomijane. */
    public static function shortcode(array $options): string
    {
        $o = static::normalize($options);
        $parts = [];
        if ($o['category']) {
            $parts[] = 'kategoria='.$o['category'];
        }
        if ($o['limit'] !== self::DEFAULT_LIMIT) {
            $parts[] = 'liczba='.$o['limit'];
        }
        if ($o['sort'] !== 'najnowsze') {
            $parts[] = 'sortowanie='.$o['sort'];
        }
        if ($o['layout'] !== 'lista') {
            $parts[] = 'wyglad='.$o['layout'];
        }

        return '[aktualnosci'.($parts ? ':'.implode(';', $parts) : '').']';
    }

    /** @return Collection<int, News> */
    public static function items(array $options): Collection
    {
        $o = static::normalize($options);
        $current = request()->route()?->parameter('news');

        $query = News::query()->withoutGlobalScope('editor_scope')->forCurrentSite()->published()
            ->when($o['category'], fn ($q, $id) => $q->where('news_category_id', $id))
            ->when($current instanceof News, fn ($q) => $q->whereKeyNot($current->id));

        $news = $query->orderByDesc('published_at')->limit($o['limit'])->get();

        if ($o['sort'] === 'alfabet') {
            $collator = class_exists(\Collator::class) ? new \Collator('pl_PL') : null;
            $news = $news->sort(fn (News $a, News $b) => $collator
                ? $collator->compare((string) $a->title, (string) $b->title)
                : strcasecmp((string) $a->title, (string) $b->title))->values();
        }

        return $news;
    }

    public static function render(array $options): string
    {
        $o = static::normalize($options);
        $news = static::items($o);
        if ($news->isEmpty()) {
            return '';
        }

        $category = $o['category'] ? NewsCategory::find($o['category']) : null;

        return View::make('partials.content-link-list', [
            'items' => $news->map(fn (News $n) => [
                'title' => $n->title,
                'url' => site_route('news.show', $n),
                'excerpt' => $o['layout'] === 'lista' ? null : (filled($n->excerpt) ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $n->excerpt)), 160) : meta_excerpt($n->content)),
                'date' => $n->published_at,
                'children' => [],
            ])->all(),
            'layout' => $o['layout'],
            'label' => $category ? 'Aktualności: '.$category->name : 'Aktualności',
        ])->render();
    }

    /** Kategorie do wyboru w edytorach. @return array<int, array{id: int, name: string}> */
    public static function categoryOptions(): array
    {
        return NewsCategory::query()->orderBy('order')->orderBy('name')->get(['id', 'name'])
            ->map(fn (NewsCategory $c) => ['id' => $c->id, 'name' => (string) $c->name])->all();
    }
}
