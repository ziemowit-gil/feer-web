<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Poll;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use App\Support\SubstackFeed;
use Illuminate\Support\Facades\Cache;

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
    /** Wyświetla stronę główną z danymi wszystkich włączonych modułów (hero, aktualności, ankieta itp.). */
    public function index()
    {
        $settings = SiteSetting::current();

        $slides = $settings->isModuleEnabled('hero') ? HeroSlide::orderBy('order')->get() : collect();

        if ($settings->hero_mission_slide && $slides->isNotEmpty()) {
            $mottoTtl    = $settings->cacheEnabled('pages') ? $settings->cacheTtl('page_item', 3600) : 0;
            $missionText = ($mottoTtl > 0
                ? Cache::remember('page_about_motto', $mottoTtl, fn () => Page::where('type', 'about')->value('about_motto'))
                : Page::where('type', 'about')->value('about_motto')
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
        $newsQuery = fn () => News::published()->with('category')->orderByDesc('published_at')->limit(3)->get();
        $news = collect();
        if ($settings->isModuleEnabled('news')) {
            if ($newsTtl > 0) {
                $news = Cache::remember('news_latest3', $newsTtl, $newsQuery);
                if (! $news instanceof \Illuminate\Database\Eloquent\Collection) {
                    Cache::forget('news_latest3');
                    $news = $newsQuery();
                }
            } else {
                $news = $newsQuery();
            }
        }

        $events = $settings->isModuleEnabled('events')
            ? Event::upcoming()->limit(3)->get()
            : collect();

        $poll = $settings->isModuleEnabled('polls') ? Poll::active() : null;

        $quickLinks = $settings->isModuleEnabled('quick_actions') ? QuickAction::orderBy('order')->get() : collect();

        $gallery = $settings->isModuleEnabled('gallery') ? GalleryImage::orderBy('order')->get() : collect();

        $partners = $settings->isModuleEnabled('partners') ? Partner::orderBy('order')->get() : collect();

        $substackPosts = $settings->substack_url ? SubstackFeed::posts($settings->substack_url) : [];

        $sectionOrder = $settings->orderedHomepageSections();

        return view('home', compact('slides', 'news', 'events', 'poll', 'quickLinks', 'gallery', 'partners', 'sectionOrder', 'substackPosts'));
    }
}
