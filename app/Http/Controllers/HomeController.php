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

    /** Renderuje stronę główną w szablonie NGO/fundacja (rozbudowanym i mieszanym). */
    private function ngoHome(SiteSetting $settings, Collection $slides, Collection $partners, string $view = 'templates.ngo.home')
    {
        // Trzy aktualności — tyle samo co w szablonie klasycznym.
        $newsItems = $settings->isModuleEnabled('news')
            ? News::published()->forCurrentSite()->with('category')->orderByDesc('published_at')->limit(3)->get()
            : collect();

        $projects = $settings->isModuleEnabled('projects')
            ? Project::forCurrentSite()->where('is_published', true)->orderBy('order')->limit(3)->get()
            : collect();

        $events = $settings->isModuleEnabled('events')
            ? Event::upcoming()->forCurrentSite()->limit(3)->get()
            : collect();

        return view($view, compact('slides', 'newsItems', 'projects', 'events', 'partners'));
    }

    /** Wyświetla stronę główną z danymi wszystkich włączonych modułów (hero, aktualności, ankieta itp.). */
    public function index()
    {
        $settings = SiteSetting::current();

        $slides = $settings->isModuleEnabled('hero') ? HeroSlide::forCurrentSite()->orderBy('order')->get() : collect();

        if ($settings->hero_mission_slide && $slides->isNotEmpty()) {
            $mottoTtl    = $settings->cacheEnabled('pages') ? $settings->cacheTtl('page_item', 3600) : 0;
            $missionText = ($mottoTtl > 0
                ? Cache::remember("page_about_motto_{$settings->id}", $mottoTtl, fn () => Page::forCurrentSite()->where('type', 'about')->value('about_motto'))
                : Page::forCurrentSite()->where('type', 'about')->value('about_motto')
            ) ?: $settings->tagline;
            if (filled($missionText)) {
                $missionSlide = (object) [
                    'mission_text'   => $missionText,
                    'logo_url'       => $settings->logoUrl(),
                    'site_name'      => $settings->site_name,
                    'mission_bg'     => $settings->hero_mission_bg ?? 'brand',
                    'mission_img_url' => $settings->missionSlideImageUrl(),
                ];
                $position = max(0, min((int) ($settings->hero_mission_order ?? 1) - 1, $slides->count()));
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

        $sectionOrder = $settings->orderedHomepageSections();

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
        if ($template === 'federacja') {
            return $this->ngoHome($settings, $slides, $partners, 'templates.federacja.home');
        }

        return view('home', compact('slides', 'news', 'events', 'poll', 'quickLinks', 'gallery', 'partners', 'sectionOrder', 'substackPosts'));
    }
}
