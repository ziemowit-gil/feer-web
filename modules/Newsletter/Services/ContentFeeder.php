<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use App\Models\EducationalMaterial;
use App\Models\Event;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Newsletter\Models\NewsletterCampaign;

/**
 * Treść dynamiczna z CMS do bloku „Najnowsze aktualności" (i pokrewnych).
 *
 * Blok w szablonie Mosaico zostawia w HTML znacznik:
 *   <div data-nl-feed="news" data-nl-limit="3" data-nl-category="" data-nl-since="last_send"
 *        data-nl-sort="newest|category" data-nl-layout="list|grid" data-nl-personal="0|1">…placeholder…</div>
 * a `inject()` podmienia go na wyrenderowane pozycje w chwili wysyłki/podglądu.
 */
class ContentFeeder
{
    public const SOURCES = [
        'news'      => 'Aktualności',
        'events'    => 'Nadchodzące wydarzenia',
        'blog'      => 'Wiem FEER (blog)',
        'materials' => 'Materiały edukacyjne',
    ];

    public const SORTS = ['newest' => 'Od najnowszych', 'category' => 'Według kategorii', 'oldest' => 'Od najstarszych'];

    /**
     * @param array{limit?:int, category?:string|int|null, since?:string|int|null, sort?:string, site_id?:int|null, topic_filter?:array|null}
     * @return Collection<int, FeedItem>
     */
    public function items(string $source, array $opts = [], ?NewsletterCampaign $campaign = null): Collection
    {
        $limit = max(1, min(12, (int) ($opts['limit'] ?? 3)));
        $since = $this->sinceDate($opts['since'] ?? null, $campaign);

        $items = match ($source) {
            'news'      => $this->news($opts, $since, $limit * 3),
            'events'    => $this->events($opts, $limit * 3),
            'blog'      => $this->blog($since, $limit * 3),
            'materials' => $this->materials($opts, $since, $limit * 3),
            default     => collect(),
        };

        if (! empty($opts['topic_filter'])) {
            $topics = (array) $opts['topic_filter'];
            $items = $items->filter(fn (FeedItem $i) => $i->topic === null || in_array($i->topic, $topics, true));
        }

        $sort = $opts['sort'] ?? 'newest';
        $items = match ($sort) {
            'category' => $items->sortBy([fn ($a, $b) => strcmp((string) $a->category, (string) $b->category)], SORT_NATURAL | SORT_FLAG_CASE)
                               ->groupBy(fn (FeedItem $i) => (string) $i->category)
                               ->map(fn (Collection $g) => $g->sortByDesc(fn (FeedItem $i) => $i->publishedAt?->timestamp ?? 0))
                               ->flatten(1),
            'oldest'   => $items->sortBy(fn (FeedItem $i) => $i->publishedAt?->timestamp ?? 0),
            default    => $items->sortByDesc(fn (FeedItem $i) => $i->publishedAt?->timestamp ?? 0),
        };

        return $items->take($limit)->values();
    }

    /** Lista kategorii aktualności do selecta w edytorze. */
    public function newsCategories(): array
    {
        return NewsCategory::orderBy('order')->orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->slug => $c->name])->all();
    }

    /**
     * Podmienia znaczniki data-nl-feed w HTML kampanii na wyrenderowane pozycje.
     * Zwraca HTML i listę id użytych pozycji (snapshot do audytu i „od ostatniej wysyłki").
     *
     * @return array{html:string, used:array<string,int[]>, newest_at:?\Carbon\CarbonInterface}
     */
    public function inject(string $html, ?NewsletterCampaign $campaign = null, ?Subscriber $subscriber = null): array
    {
        $used = [];
        $newest = null;

        $html = (string) preg_replace_callback(
            '/<(div|table)\b([^>]*\bdata-nl-feed\s*=\s*("|\')([a-z]+)\3[^>]*)>(.*?)<\/\1>\s*<!--\s*\/nl-feed\s*-->/is',
            function (array $m) use (&$used, &$newest, $campaign, $subscriber): string {
                $attrs  = $m[2];
                $source = $m[4];
                $opts   = [
                    'limit'    => (int) ($this->attr($attrs, 'limit') ?: 3),
                    'category' => $this->attr($attrs, 'category'),
                    'since'    => $this->attr($attrs, 'since') ?: 'last_send',
                    'sort'     => $this->attr($attrs, 'sort') ?: 'newest',
                ];
                $layout   = $this->attr($attrs, 'layout') ?: 'list';
                $personal = in_array(strtolower((string) $this->attr($attrs, 'personal')), ['1', 'true', 'on'], true);
                if ($personal && $subscriber && ! empty($subscriber->topics)) {
                    $opts['topic_filter'] = $subscriber->topics;
                }
                $style = $this->styleFromAttrs($attrs);

                $items = $this->items($source, $opts, $campaign);
                $used[$source] = array_merge($used[$source] ?? [], $items->pluck('id')->all());
                foreach ($items as $i) {
                    if ($i->publishedAt && (! $newest || $i->publishedAt->gt($newest))) {
                        $newest = $i->publishedAt;
                    }
                }

                return $this->renderItems($items, $layout, $opts['sort'] === 'category', $style, $campaign);
            },
            $html
        );

        return ['html' => $html, 'used' => $used, 'newest_at' => $newest];
    }

    /** HTML pozycji (tabelowy, bezpieczny dla klientów poczty). */
    public function renderItems(Collection $items, string $layout = 'list', bool $groupByCategory = false, array $style = [], ?NewsletterCampaign $campaign = null): string
    {
        $font  = $style['font'] ?? 'Montserrat, Arial, Helvetica, sans-serif';
        $text  = $style['text'] ?? '#1D1D1A';
        $muted = $style['muted'] ?? '#4A4A47';
        $link  = $style['link'] ?? '#1752BF';
        $size  = (int) ($style['size'] ?? 15);

        if ($items->isEmpty()) {
            return '<p style="margin:0;padding:8px 0;font-family:' . e($font) . ';font-size:' . $size . 'px;color:' . e($muted) . '">Brak nowych pozycji w tym okresie.</p>';
        }

        $out = '';
        $lastCategory = null;
        $grid = $layout === 'grid';

        if ($grid) {
            $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
        }

        $col = 0;
        foreach ($items as $item) {
            if ($groupByCategory && ! $grid && $item->category !== $lastCategory) {
                $lastCategory = $item->category;
                $badge = $item->categoryColor ? 'border-left:4px solid ' . e($item->categoryColor) . ';padding-left:10px;' : '';
                $out .= '<h3 style="margin:18px 0 8px;font-family:' . e($font) . ';font-size:' . ($size + 2) . 'px;font-weight:800;color:' . e($text) . ';' . $badge . '">' . e((string) $item->category) . '</h3>';
            }

            $meta = [];
            if ($item->publishedAt) {
                $meta[] = $item->publishedAt->format('d.m.Y');
            }
            if ($item->category && ! $groupByCategory) {
                $meta[] = $item->category;
            }
            $metaHtml = $meta ? '<p style="margin:0 0 4px;font-family:' . e($font) . ';font-size:13px;color:' . e($muted) . '">' . e(implode(' · ', $meta)) . '</p>' : '';
            $img = $item->imageUrl
                ? '<a href="' . e($item->url) . '" style="text-decoration:none"><img src="' . e($item->imageUrl) . '" width="' . ($grid ? 258 : 180) . '" alt="' . e($item->imageAlt) . '" style="display:block;width:100%;max-width:' . ($grid ? 258 : 180) . 'px;height:auto;border-radius:6px;border:0" /></a>'
                : '';
            $body = $metaHtml
                . '<h3 style="margin:0 0 8px;font-family:' . e($font) . ';font-size:' . ($size + 3) . 'px;line-height:1.3;font-weight:800"><a href="' . e($item->url) . '" style="color:' . e($link) . ';text-decoration:none">' . e($item->title) . '</a></h3>'
                . ($item->excerpt !== '' ? '<p style="margin:0 0 8px;font-family:' . e($font) . ';font-size:' . $size . 'px;line-height:1.5;color:' . e($text) . '">' . e(Str::limit($item->excerpt, 220)) . '</p>' : '')
                . '<a href="' . e($item->url) . '" style="font-family:' . e($font) . ';font-size:' . $size . 'px;font-weight:700;color:' . e($link) . ';text-decoration:underline">Czytaj dalej</a>';

            if ($grid) {
                $out .= '<td width="50%" valign="top" style="padding:0 9px 18px 0">' . $img . '<div style="padding-top:8px">' . $body . '</div></td>';
                if (++$col % 2 === 0) {
                    $out .= '</tr><tr>';
                }
            } else {
                $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px"><tr>'
                    . ($img ? '<td width="180" valign="top" style="padding-right:16px">' . $img . '</td>' : '')
                    . '<td valign="top">' . $body . '</td></tr></table>';
            }
        }

        if ($grid) {
            $out .= '</tr></table>';
        }

        return $out;
    }

    // ── Źródła ───────────────────────────────────────────────────────────────

    private function news(array $opts, $since, int $take): Collection
    {
        $q = News::query()->published()->with('category')->orderByDesc('published_at');
        if (! empty($opts['site_id'])) {
            $q->where(fn ($w) => $w->where('site_id', $opts['site_id'])->orWhereNull('site_id'));
        }
        if (! empty($opts['category'])) {
            $cat = $opts['category'];
            $q->whereHas('category', fn ($c) => is_numeric($cat) ? $c->where('id', (int) $cat) : $c->where('slug', $cat));
        }
        if ($since) {
            $q->where('published_at', '>', $since);
        }

        return $q->take($take)->get()->map(fn (News $n) => new FeedItem(
            source: 'news', id: (int) $n->id, title: (string) $n->title,
            excerpt: trim(strip_tags((string) ($n->excerpt ?: Str::limit(strip_tags((string) $n->content), 200)))),
            url: $this->absolute(route('news.show', ['news' => $n->slug], false)),
            imageUrl: $n->imageUrlOrDefault(), imageAlt: (string) ($n->image_alt ?: $n->title),
            publishedAt: $n->published_at, category: $n->category?->name, categorySlug: $n->category?->slug,
            categoryColor: $n->category?->color, topic: $this->topicForCategory($n->category?->slug),
        ));
    }

    private function events(array $opts, int $take): Collection
    {
        if (! class_exists(Event::class) || ! \Illuminate\Support\Facades\Schema::hasTable('events')) {
            return collect();
        }
        $q = Event::query()->where('is_published', true)->whereNull('archived_at')
            ->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');

        return $q->take($take)->get()->map(fn (Event $e) => new FeedItem(
            source: 'events', id: (int) $e->id, title: (string) $e->title,
            excerpt: trim(strip_tags((string) ($e->lead ?: Str::limit(strip_tags((string) $e->description), 200)))) . ($e->starts_at ? ' (' . $e->starts_at->format('d.m.Y, H:i') . ')' : ''),
            url: $this->absolute(route('events.show', ['event' => $e->slug], false)),
            imageUrl: method_exists($e, 'getFirstMediaUrl') ? ($e->getFirstMediaUrl('image') ?: null) : null, imageAlt: (string) $e->title,
            publishedAt: $e->starts_at, category: $e->type ? Str::ucfirst((string) $e->type) : null, topic: 'events',
        ));
    }

    private function blog($since, int $take): Collection
    {
        if (! class_exists(\Modules\Blog\Models\BlogArticle::class)) {
            return collect();
        }
        try {
            $q = \Modules\Blog\Models\BlogArticle::query()->published()->orderByDesc('published_at');
            if ($since) {
                $q->where('published_at', '>', $since);
            }

            return $q->take($take)->get()->map(fn ($a) => new FeedItem(
                source: 'blog', id: (int) $a->id, title: (string) $a->title,
                excerpt: trim(strip_tags((string) ($a->excerpt ?: Str::limit(strip_tags((string) $a->body), 200)))),
                url: $this->absolute(route('blog.show', ['article' => $a->slug], false)),
                imageUrl: null, imageAlt: (string) $a->title, publishedAt: $a->published_at, category: 'Wiem FEER', topic: 'blog',
            ));
        } catch (\Throwable) {
            return collect();
        }
    }

    private function materials(array $opts, $since, int $take): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('educational_materials')) {
            return collect();
        }
        $q = EducationalMaterial::query()->where('is_published', true)->orderByDesc('created_at');
        if ($since) {
            $q->where('created_at', '>', $since);
        }
        if (! empty($opts['category'])) {
            $q->where('category', $opts['category']);
        }

        return $q->take($take)->get()->map(fn (EducationalMaterial $m) => new FeedItem(
            source: 'materials', id: (int) $m->id, title: (string) $m->title,
            excerpt: trim(strip_tags((string) $m->description)),
            url: $this->absolute(route('materials.index', [], false) . '#material-' . $m->id),
            imageUrl: null, imageAlt: (string) $m->title, publishedAt: $m->created_at,
            category: $m->category ? (string) $m->category : null, topic: 'materials',
        ));
    }

    // ── Pomocnicze ───────────────────────────────────────────────────────────

    private function sinceDate(mixed $since, ?NewsletterCampaign $campaign): ?\Carbon\CarbonInterface
    {
        if ($since === null || $since === '' || $since === 'all') {
            return null;
        }
        if ($since === 'last_send') {
            // Pierwsza wysyłka (brak poprzedniego biegu) = bez ograniczenia daty.
            $parent = $campaign?->parent_campaign_id ? NewsletterCampaign::find($campaign->parent_campaign_id) : $campaign;

            return $parent?->last_feed_item_at ?? $parent?->last_run_at;
        }
        if (is_numeric($since)) {
            return now()->subDays((int) $since);
        }

        return null;
    }

    private function topicForCategory(?string $slug): ?string
    {
        return match ($slug) {
            'etr', 'latwy-odczyt' => 'etr',
            'dzialania', 'projekty' => 'projects',
            default => 'news',
        };
    }

    private function attr(string $attrs, string $name): ?string
    {
        return preg_match('/\bdata-nl-' . $name . '\s*=\s*("|\')([^"\']*)\1/i', $attrs, $m) ? html_entity_decode($m[2]) : null;
    }

    private function styleFromAttrs(string $attrs): array
    {
        $style = [];
        foreach (['font', 'text', 'muted', 'link', 'size'] as $k) {
            if (($v = $this->attr($attrs, 'style-' . $k)) !== null && $v !== '') {
                $style[$k] = $v;
            }
        }

        return $style;
    }

    private function absolute(string $path): string
    {
        return preg_match('#^https?://#', $path) ? $path : rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
