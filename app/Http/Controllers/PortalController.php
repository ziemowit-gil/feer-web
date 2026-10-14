<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\News;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Throwable;

/**
 * Portal kategorii działań (/portal/{adres}), wzorowany na portalach tematycznych serwisów miejskich:
 * slider, obok menu działań kategorii z rozwijanymi podstronami i „Na skróty”, niżej aktualności
 * (wyróżniona + lista) i widżety w kolejności ustawionej w panelu (Działania → Kategorie).
 *
 * Metody: show(), feed().
 */
class PortalController extends Controller
{
    /** Ile wpisów pokazują listy na portalu. */
    private const NEWS_LIMIT = 7;

    private const EVENTS_LIMIT = 4;

    private const RSS_LIMIT = 5;

    public function show(string $portal)
    {
        $category = $this->category($portal);
        $projects = $this->projects($category);
        $projectIds = $this->allProjectIds($category);
        $widgets = $category->portalWidgets();

        $news = $this->news($projectIds, self::NEWS_LIMIT);
        $events = in_array('events', $widgets, true) || in_array('stats', $widgets, true) ? $this->events() : collect();

        return view('portal.show', [
            'category' => $category,
            'title' => $category->portal_title ?: $category->name,
            'projects' => $projects,
            'navItems' => $category->portalNavItems($projects),
            'slides' => $category->portalLayout('slider') === 'hide' ? collect() : $this->slides($category, $projects),
            'sliderDuration' => $this->sliderDuration($category),
            'widgets' => $widgets,
            'news' => $news,
            'events' => $events,
            'rssItems' => in_array('rss', $widgets, true) ? $this->externalFeed((string) $category->portal_rss_url) : collect(),
            'coordinators' => in_array('contact', $widgets, true) ? $this->coordinators($projects) : collect(),
            'stats' => [
                'projects' => $projects->count(),
                'news' => $projectIds ? News::published()->whereIn('project_id', $projectIds)->count() : 0,
                'events' => $events->count(),
            ],
        ]);
    }

    /** Kanał RSS portalu: aktualności z działań kategorii i same działania, od najnowszych. */
    public function feed(string $portal)
    {
        $category = $this->category($portal);
        $settings = SiteSetting::current();
        $title = $category->portal_title ?: $category->name;

        $items = $this->news($this->allProjectIds($category), 30)->map(fn (News $n) => new Fluent([
            'title' => $n->title,
            'url' => route('news.show', $n),
            'date' => $n->published_at,
            'summary' => trim(strip_tags((string) $n->excerpt)) ?: Str::limit(trim(strip_tags((string) $n->content)), 300),
            'category' => 'Aktualności',
        ]))->concat($this->projects($category)->map(fn (Project $p) => new Fluent([
            'title' => $p->title,
            'url' => route('projects.show', $p),
            'date' => $p->created_at,
            'summary' => (string) $p->teaser(300),
            'category' => $category->portalWidgetTitle('projects'),
        ])))->filter(fn ($i) => $i->date)->sortByDesc(fn ($i) => $i->date->getTimestamp())->take(30)->values();

        $xml = view('portal.rss', [
            'settings' => $settings,
            'title' => $settings->site_name.' — '.$title,
            'description' => $category->portalOption('lead') ?: ($category->portal_intro ? Str::limit(strip_tags($category->portal_intro), 300) : "Aktualności i nowości: {$title}."),
            'siteUrl' => route('portal.show', $category->portal_slug),
            'selfUrl' => route('portal.feed', $category->portal_slug),
            'items' => $items,
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'X-Robots-Tag' => $settings->allow_indexing ? 'index' : 'noindex',
        ]);
    }

    private function category(string $portal): Category
    {
        abort_unless(Schema::hasColumn('categories', 'portal_slug') && Category::portalsEnabled(), 404);

        return Category::where('portal_slug', $portal)->firstOrFail();
    }

    /** Opublikowane działania główne kategorii (z podstronami do menu bocznego). */
    private function projects(Category $category): Collection
    {
        return $category->publishedProjects()->with(['publishedPages' => fn ($q) => $q->whereNull('parent_id')])->get();
    }

    /** Id działań kategorii razem z poddziałaniami (aktualności bywają podpięte pod poddziałanie). */
    private function allProjectIds(Category $category): array
    {
        $ids = Project::forCurrentSite()->where('category_id', $category->id)->where('is_published', true)->pluck('id')->all();
        $frontier = $ids;
        while ($frontier) {
            $frontier = Project::forCurrentSite()->whereIn('parent_id', $frontier)->where('is_published', true)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    private function news(array $projectIds, int $limit): Collection
    {
        if (! $projectIds) {
            return collect();
        }

        return News::published()->whereIn('project_id', $projectIds)->orderByDesc('published_at')->limit($limit)->get();
    }

    private function events(): Collection
    {
        try {
            return Event::query()->forCurrentSite()->upcoming()->limit(self::EVENTS_LIMIT)->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * Slajdy: wybrany w panelu slider, a bez niego — zdjęcia działań kategorii
     * (tytuł działania, zajawka i przycisk do jego strony).
     */
    private function slides(Category $category, Collection $projects): Collection
    {
        if ($category->portal_slider_id && Schema::hasTable('sliders')) {
            $slider = \App\Models\Slider::query()->forCurrentSite()->with('slides.media')->find($category->portal_slider_id);
            $slides = $slider ? $slider->slides->filter(fn ($s) => $s->title || $s->image_url)->values() : collect();
            if ($slides->isNotEmpty()) {
                return $slides;
            }
        }

        return $projects->filter(fn (Project $p) => filled($p->image_url))->take(5)->map(fn (Project $p) => new Fluent([
            'title' => $p->title,
            'text' => $p->teaser(160),
            'image_url' => $p->image_url,
            'image_alt' => $p->image_alt ?: '',
            'button_label' => $category->portalLabel('slide_button'),
            'button_url' => route('projects.show', $p),
        ]))->values();
    }

    private function sliderDuration(Category $category): ?int
    {
        if (! $category->portal_slider_id || ! Schema::hasTable('sliders')) {
            return null;
        }

        return \App\Models\Slider::query()->forCurrentSite()->whereKey($category->portal_slider_id)->value('duration');
    }

    /** Koordynatorzy działań kategorii (bez powtórzeń), z danymi kontaktowymi z działania. */
    private function coordinators(Collection $projects): Collection
    {
        return $projects->filter(fn (Project $p) => ($p->show_coordinator ?? true) && (filled($p->coordinator_name) || filled($p->coordinator_email)))
            ->map(fn (Project $p) => new Fluent([
                'name' => trim((string) $p->coordinator_name),
                'email' => trim((string) $p->coordinator_email),
                'phone' => trim((string) $p->coordinator_phone),
                'project' => $p->title,
                'photo' => $p->coordinatorPhoto(),
            ]))
            ->unique(fn ($c) => mb_strtolower($c->email ?: $c->name))
            ->take(4)->values();
    }

    /**
     * Wpisy z zewnętrznego kanału RSS/Atom (pamięć podręczna 30 min). Błąd pobrania albo zły format
     * daje pustą listę — widżet wtedy się nie pokazuje, portal działa dalej.
     */
    private function externalFeed(string $url): Collection
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return collect();
        }

        return Cache::remember('portal_rss:'.md5($url), 1800, function () use ($url) {
            try {
                $body = Http::timeout(5)->accept('application/rss+xml, application/atom+xml, application/xml, text/xml')->get($url)->throw()->body();
                $xml = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
                if (! $xml) {
                    return collect();
                }
                $entries = isset($xml->channel->item) ? $xml->channel->item : ($xml->entry ?? []);
                $items = collect();
                foreach ($entries as $entry) {
                    $link = (string) ($entry->link['href'] ?? '') ?: trim((string) $entry->link);
                    $date = (string) ($entry->pubDate ?? $entry->updated ?? $entry->published ?? '');
                    if (! preg_match('#^https?://#i', $link)) {
                        continue; // tylko bezpieczne odnośniki http(s)
                    }
                    $items->push(new Fluent([
                        'title' => Str::limit(trim(strip_tags((string) $entry->title)), 160),
                        'url' => $link,
                        'date' => $date !== '' ? rescue(fn () => \Illuminate\Support\Carbon::parse($date), null, false) : null,
                    ]));
                    if ($items->count() >= self::RSS_LIMIT) {
                        break;
                    }
                }

                return $items;
            } catch (Throwable) {
                return collect();
            }
        });
    }
}
