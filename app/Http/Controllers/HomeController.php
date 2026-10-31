<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Poll;
use App\Models\Project;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use App\Support\SubstackFeed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Strona główna serwisu — agreguje dane ze wszystkich włączonych modułów
 * (hero, aktualności, wydarzenia, ankieta, szybkie akcje, galeria, partnerzy, Substack).
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class HomeController extends Controller
{
    /** Renderuje stronę główną w szablonie gminy. */
    private function municipalityHome(SiteSetting $settings, Collection $slides, Collection $partners)
    {
        $newsSidebar = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(3)->get()
            : collect();

        $newsGrid = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->skip(3)->limit(8)->get()
            : collect();

        $shortcuts = collect();
        if ($settings->municipality_shortcuts_slug) {
            $tilesPage = Page::forCurrentSite()->where('slug', $settings->municipality_shortcuts_slug)
                ->where('type', 'tiles_grid')
                ->first();
            if ($tilesPage && !empty($tilesPage->tiles)) {
                $shortcuts = collect($tilesPage->tiles);
            }
        }

        return view('templates.municipality.home', compact('slides', 'newsSidebar', 'newsGrid', 'shortcuts', 'partners'));
    }

    /**
     * Moduły szkoleniowe strony głównej „FEER IS”: wyróżnione szkolenia (typ „Szkolenie”; gdy żadne nie jest
     * wyróżnione, najnowsze), najbliższe terminy ze wszystkich szkoleń oraz adres katalogu szkoleń.
     *
     * @return array{trainingOffers: Collection, trainingEditions: Collection, trainingCatalogUrl: ?string}
     */
    private function trainingModules(): array
    {
        $offers = Page::forCurrentSite()->where('type', 'training_offer')->where('is_published', true)->orderBy('order')->orderBy('title')->get();

        $featured = $offers->filter(fn (Page $p) => ($p->typeData()['featured'] ?? '0') == '1');
        $cards = ($featured->isNotEmpty() ? $featured : $offers)->take(6)->map(function (Page $p) {
            $td = $p->typeData();

            return [
                'title' => $p->title, 'lead' => $td['lead'] ?? $p->meta_description, 'url' => $p->publicUrl(),
                'duration' => $td['duration'] ?? null, 'mode' => $td['mode'] ?? null, 'price' => $td['price'] ?? null,
                'category' => $td['category'] ?? null,
            ];
        })->values();

        $today = now()->startOfDay();
        $editions = $offers->flatMap(function (Page $p) use ($today) {
            return collect($p->typeData()['editions'] ?? [])
                ->filter(fn ($e) => filled($e['date'] ?? null) && \Illuminate\Support\Carbon::parse($e['date'])->gte($today))
                ->map(fn ($e) => [
                    'date' => \Illuminate\Support\Carbon::parse($e['date']), 'place' => $e['place'] ?? null, 'status' => $e['status'] ?? 'open',
                    'title' => $p->title, 'url' => filled($e['url'] ?? null) ? $e['url'] : $p->publicUrl(),
                ]);
        })->sortBy('date')->take(6)->values();

        $catalog = Page::forCurrentSite()->where('type', 'training_catalog')->where('is_published', true)->orderBy('order')->first();

        return ['trainingOffers' => $cards, 'trainingEditions' => $editions, 'trainingCatalogUrl' => $catalog?->publicUrl()];
    }

    /** Widoczne teraz slajdy strony głównej wskazanej witryny (bez względu na bieżącą witrynę). */
    private function heroSlidesOf(SiteSetting $site): Collection
    {
        $homeSliderId = \App\Models\Slider::query()->where('site_id', $site->id)->where('is_home', true)->orderBy('id')->value('id') ?? 0;

        return HeroSlide::query()->where('site_id', $site->id)
            ->where(fn ($q) => $q->whereNull('slider_id')->orWhere('slider_id', $homeSliderId))
            ->orderBy('order')->orderBy('id')->get()
            ->filter(fn (HeroSlide $slide) => $slide->isVisibleNow())->values();
    }

    /**
     * Zakładki portalu usług: dla każdej wpisanej w ustawieniach strony jej opublikowane podstrony jako „usługi”
     * (tytuł, opis z meta description). Zakładki bez usług są pomijane.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, services: \Illuminate\Support\Collection}>
     */
    private function servicesTabs(SiteSetting $settings): Collection
    {
        return collect($settings->servicesTabs())->map(function (array $tab) {
            $page = Page::forCurrentSite()->where('is_published', true)->where('slug', $tab['slug'])->first();
            $services = $page
                ? $page->publishedChildren()->get()->map(fn (Page $child) => [
                    'title' => $child->title,
                    'description' => $child->meta_description,
                    'icon' => ($child->layout_data ?? [])['icon'] ?? null,
                    'url' => $child->publicUrl(),
                ])
                : collect();

            return ['label' => $tab['label'], 'services' => $services];
        })->filter(fn (array $tab) => $tab['services']->isNotEmpty())->values();
    }

    /** Renderuje stronę główną w szablonie NGO/fundacja (rozbudowanym i mieszanym). */
    private function ngoHome(SiteSetting $settings, Collection $slides, Collection $partners, string $view = 'templates.ngo.home', ?Collection $quickLinks = null)
    {
        // Trzy aktualności — tyle samo co w szablonie klasycznym.
        $newsItems = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(3)->get()
            : collect();

        // Tylko aktywne (nie zakończone) działania — teaser na stronie głównej ma
        // pokazywać bieżącą działalność, nie zamknięte/zarchiwizowane projekty.
        $projects = $settings->isModuleEnabled('projects')
            ? Project::forCurrentSite()->where('is_published', true)->where('is_completed', false)->onHome()->orderBy('order')->limit(6)->get()
            : collect();

        $events = $settings->isModuleEnabled('events')
            ? Event::upcoming()->forCurrentSite()->limit(3)->get()
            : collect();

        $quickLinks ??= collect();

        // Ankieta (moduł polls) — szablon FEER pokazuje ją obok „Na skróty”.
        $poll = $settings->isModuleEnabled('polls') ? Poll::active() : null;

        return view($view, compact('slides', 'newsItems', 'projects', 'events', 'partners', 'quickLinks', 'poll'));
    }

    /** Renderuje stronę główną w szablonie "ngo_3" (hero, skróty, statystyki, newsy, działania, wydarzenia, newsletter). */
    private function ngo3Home(SiteSetting $settings, Collection $slides, Collection $partners, Collection $quickLinks)
    {
        $newsItems = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(3)->get()
            : collect();

        // Tylko aktywne (nie zakończone) działania — ngo_3 pokazuje bieżącą działalność, nie archiwum.
        $projects = $settings->isModuleEnabled('projects')
            ? Project::forCurrentSite()->with('category')->where('is_published', true)->where('is_completed', false)->onHome()->orderBy('order')->limit(6)->get()
            : collect();

        $events = $settings->isModuleEnabled('events')
            ? Event::upcoming()->forCurrentSite()->limit(3)->get()
            : collect();

        // Statystyki biorą się ze strony "O organizacji" (about_stats), żeby nie
        // duplikować tych samych liczb w dwóch miejscach panelu. Gdy strona
        // "O organizacji" nie istnieje albo nie ma jeszcze statystyk, spada do
        // domyślnego układu z ngo3Stats() (ustawienia szablonu).
        $aboutPage = Page::where('type', 'about')->forCurrentSite()->first();
        $stats = ($aboutPage && filled($aboutPage->about_stats)) ? $aboutPage->about_stats : $settings->ngo3Stats();

        return view('templates.ngo_3.home', compact('slides', 'newsItems', 'projects', 'events', 'partners', 'quickLinks', 'stats'));
    }

    /** Renderuje stronę główną w szablonie "wrzos" (siatka aktualności 4×2, blok "Kim jesteśmy?", karty wartości). */
    private function wrzosHome(SiteSetting $settings, Collection $partners)
    {
        $newsItems = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(8)->get()
            : collect();

        return view('templates.wrzos.home', compact('newsItems', 'partners'));
    }

    /** Renderuje stronę główną w szablonie "vm" (slider z kartą, blok powitalny, aktualności 4×, działania, Wiedza, współpraca, newsletter). */
    private function vmHome(SiteSetting $settings, Collection $slides, Collection $partners)
    {
        $newsItems = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(4)->get()
            : collect();

        // Tylko aktywne (nie zakończone) działania — jak w pozostałych szablonach NGO.
        $projects = $settings->isModuleEnabled('projects')
            ? Project::forCurrentSite()->where('is_published', true)->where('is_completed', false)->onHome()->orderBy('order')->limit(6)->get()
            : collect();

        // Sekcja „Wiedza": wybrana w panelu strona i jej opublikowane podstrony.
        $knowledgePage = $settings->vm_knowledge_page_id
            ? Page::forCurrentSite()->where('is_published', true)->with('publishedChildren')->find($settings->vm_knowledge_page_id)
            : null;

        return view('templates.vm.home', compact('slides', 'newsItems', 'projects', 'knowledgePage', 'partners'));
    }

    /** Wyświetla stronę główną z danymi wszystkich włączonych modułów (hero, aktualności, ankieta itp.). */
    public function index()
    {
        // Witryna sieci rodzaju „bip" (np. bip.feer.org.pl): stroną główną jest BIP.
        if (SiteSetting::current()->isBipSite()) {
            abort_unless(SiteSetting::current()->isModuleEnabled('bip'), 404);

            return app(\App\Http\Controllers\Bip\BipController::class)->index(request());
        }

        // Własna domena bloga Wiem FEER (np. wiem.feer.org.pl): strona główna to lista wpisów.
        // Na domenie głównej i innych witrynach nic się nie zmienia.
        if (class_exists(\Modules\Blog\Support\BlogSite::class) && \Modules\Blog\Support\BlogSite::servesOwnDomain(request())) {
            request()->attributes->set('blog_site', true);

            return app(\Modules\Blog\Http\Controllers\BlogController::class)->index(request());
        }

        $settings = SiteSetting::current();

        // Slajdy slidera „Strona główna” (Slidery); przed migracją — wszystkie slajdy jak dotąd.
        $slides = ! $settings->isModuleEnabled('hero') ? collect()
            : (\Illuminate\Support\Facades\Schema::hasTable('sliders')
                // Slajdy bez slidera (np. dodane przed wprowadzeniem sliderów albo importem) też są strony głównej.
                ? HeroSlide::forCurrentSite()
                    ->where(fn ($q) => $q->whereNull('slider_id')->orWhere('slider_id', \App\Models\Slider::forPlacement('home')?->id ?? 0))
                    ->orderBy('order')->orderBy('id')->get()
                : HeroSlide::forCurrentSite()->orderBy('order')->get())
                // Slajdy z terminem (od–do) poza nim pomijamy.
                ->filter(fn (HeroSlide $slide) => $slide->isVisibleNow())->values();

        // Witryna w sieci z motywem „FEER IS” dziedziczy z witryny głównej slajdy (gdy nie ma własnych) i misję.
        $parentSite = $settings->themeVariant() === 'feer_is' && $settings->parent_site_id
            ? SiteSetting::query()->notTrashed()->find($settings->parent_site_id)
            : null;

        if ($parentSite && $slides->isEmpty()) {
            $slides = $this->heroSlidesOf($parentSite);
        }

        $ownMotto = $parentSite ? Page::forCurrentSite()->where('type', 'about')->value('about_motto') : null;
        $missionSite = $parentSite && ! filled($ownMotto) ? $parentSite : $settings;

        if (($settings->hero_mission_slide || $missionSite->hero_mission_slide) && $slides->isNotEmpty()) {
            $mottoTtl    = $missionSite->cacheEnabled('pages') ? $missionSite->cacheTtl('page_item', 3600) : 0;
            $mottoQuery  = fn () => Page::withoutGlobalScopes()->where('site_id', $missionSite->id)->where('type', 'about')->value('about_motto');
            $missionText = ($missionSite->is($settings) && filled($ownMotto) ? $ownMotto : ($mottoTtl > 0
                ? Cache::remember("page_about_motto_{$missionSite->id}", $mottoTtl, $mottoQuery)
                : $mottoQuery())
            ) ?: $missionSite->tagline;
            if (filled($missionText)) {
                $missionSlide = (object) [
                    'mission_text'   => $missionText,
                    'logo_url'       => $missionSite->logoUrl(),
                    'site_name'      => $missionSite->site_name,
                    'mission_bg'     => $missionSite->hero_mission_bg ?? 'brand',
                    'mission_img_url' => $missionSite->missionSlideImageUrl(),
                ];
                $position = max(0, min((int) ($missionSite->hero_mission_order ?? 1) - 1, $slides->count()));
                $slides = $slides->values()->take($position)->push($missionSlide)->toBase()->merge($slides->values()->slice($position));
            }
        }

        $newsTtl = $settings->isModuleEnabled('news') && $settings->cacheEnabled('news')
            ? $settings->cacheTtl('news_item', 3600)
            : 0;
        $newsQuery = fn () => News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(3)->get();
        $news = collect();
        if ($settings->isModuleEnabled('news')) {
            if ($newsTtl > 0) {
                $newsCacheKey = "news_latest3_{$settings->id}";
                $news = Cache::remember($newsCacheKey, $newsTtl, $newsQuery);
                if (! $news instanceof \Illuminate\Database\Eloquent\Collection) {
                    Cache::forget($newsCacheKey);
                    $news = $newsQuery();
                }
            } else {
                $news = $newsQuery();
            }
        }

        $events = $settings->isModuleEnabled('events')
            ? Event::upcoming()->forCurrentSite()->limit(3)->get()
            : collect();

        $poll = $settings->isModuleEnabled('polls') ? Poll::active() : null;

        $quickLinks = $settings->isModuleEnabled('quick_actions') ? QuickAction::forCurrentSite()->orderBy('order')->get() : collect();

        $gallery = $settings->isModuleEnabled('gallery') ? GalleryImage::forCurrentSite()->orderBy('order')->get() : collect();

        $partners = $settings->isModuleEnabled('partners') ? Partner::forCurrentSite()->orderBy('order')->get() : collect();

        $substackPosts = $settings->substack_url ? SubstackFeed::posts($settings->substack_url) : [];

        $sectionOrder = $settings->orderedHomepageSections(true);

        $template = $settings->site_template ?? 'default';
        if ($template === 'municipality') {
            return $this->municipalityHome($settings, $slides, $partners);
        }
        if ($template === 'ngo') {
            return $this->ngoHome($settings, $slides, $partners);
        }
        if ($template === 'ngo_mix') {
            return $this->ngoHome($settings, $slides, $partners, 'templates.ngo-mix.home');
        }

        if ($template === 'feer') {
            // Jeden przekaz: tylko pierwszy slajd (bez karuzeli), gdy włączono w Ustawienia → Strona główna.
            $heroSingle = (bool) ($settings->hero_single ?? false);
            if ($heroSingle) {
                $slides = $slides->take(1)->values();
            }
            if ($settings->themeVariant() === 'feer_is') {
                return $this->ngoHome($settings, $slides, $partners, 'templates.feer.home-feer-is', $quickLinks)
                    ->with('servicesTabs', $this->servicesTabs($settings))
                    ->with($this->trainingModules())
                    // Najbliższe terminy z oferty szkoleń (moduł „courses”), po jednym na szkolenie.
                    ->with('courseEditions', $this->upcomingCourseEditions($settings))
                    // Wyróżniony wpis, dwa kolejne na liście i trzy karty.
                    ->with('newsItems', $settings->isModuleEnabled('news')
                        ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(6)->get()
                        : collect());
            }

            return $this->ngoHome($settings, $slides, $partners, 'templates.feer.home', $quickLinks)
                ->with('gallery', $gallery)->with('heroSingle', $heroSingle)->with('substackPosts', $substackPosts);
        }
        if ($template === 'federation') {
            return $this->ngoHome($settings, $slides, $partners, 'templates.federation.home');
        }
        if ($template === 'wrzos') {
            return $this->wrzosHome($settings, $partners);
        }
        if ($template === 'vm') {
            return $this->vmHome($settings, $slides, $partners);
        }
        if ($template === 'ngo_3') {
            return $this->ngo3Home($settings, $slides, $partners, $quickLinks);
        }

        return view('home', compact('slides', 'news', 'events', 'poll', 'quickLinks', 'gallery', 'partners', 'sectionOrder', 'substackPosts'));
    }

    /** Do sześciu najbliższych terminów aktywnych szkoleń witryny z otwartą rekrutacją, po jednym na szkolenie. */
    private function upcomingCourseEditions(SiteSetting $settings)
    {
        if (! $settings->isModuleEnabled('courses') || ! \Illuminate\Support\Facades\Schema::hasTable('course_editions')) {
            return collect();
        }

        return \App\Models\CourseEdition::query()
            ->whereHas('course', fn ($q) => $q->forCurrentSite()->visible())
            ->where('enrollment_status', 'open')
            ->whereDate('starts_on', '>=', today())
            ->with('course')
            ->orderBy('starts_on')
            ->limit(24)->get()
            ->unique('course_id')->take(6)->values();
    }
}
