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
            $missionText = Page::where('type', 'about')->value('about_motto') ?: $settings->tagline;
            if ($missionText) {
                $missionSlide = (object) [
                    'mission_text'   => $missionText,
                    'logo_url'       => $settings->logoUrl(),
                    'site_name'      => $settings->site_name,
                    'mission_bg'     => $settings->hero_mission_bg ?? 'brand',
                    'mission_img_url' => $settings->missionSlideImageUrl(),
                ];
                $position = max(0, min((int) ($settings->hero_mission_order ?? 1) - 1, $slides->count()));
                $slides = $slides->values()->take($position)->push($missionSlide)->merge($slides->values()->slice($position));
            }
        }

        $news = $settings->isModuleEnabled('news')
            ? News::published()->with('category')->orderByDesc('published_at')->limit(3)->get()
            : collect();

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
