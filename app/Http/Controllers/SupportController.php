<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Publiczna strona „Wesprzyj nas" ze statystykami, galerią i listą metod wsparcia organizacji.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SupportController extends Controller
{
    /** Wyświetla stronę „Wesprzyj nas" ze statystykami, galerią i listą metod wsparcia. */
    public function index()
    {
        $settings = SiteSetting::current();

        // Liczby ze statystyk strony „O organizacji" (about_stats) — na wsparciu
        // pokazujemy do 6 kompletnych kafelków, żeby mocniej pokazać skalę działań.
        $pageTtl = $settings->cacheEnabled('pages') ? $settings->cacheTtl('page_item', 3600) : 0;
        if ($pageTtl > 0) {
            try {
                $cached = Cache::get('page_about_first');
                if ($cached instanceof Page) {
                    $aboutPage = $cached;
                } else {
                    if ($cached !== null) {
                        Cache::forget('page_about_first');
                    }
                    $aboutPage = Page::where('type', 'about')->orderBy('order')->first();
                    if ($aboutPage !== null) {
                        Cache::put('page_about_first', $aboutPage, $pageTtl);
                    }
                }
            } catch (\Throwable) {
                Cache::forget('page_about_first');
                $aboutPage = Page::where('type', 'about')->orderBy('order')->first();
            }
        } else {
            $aboutPage = Page::where('type', 'about')->orderBy('order')->first();
        }
        $stats = collect($aboutPage->about_stats ?? [])
            ->filter(fn ($s) => filled($s['value'] ?? null) && filled($s['label'] ?? null))
            ->take(6)
            ->values();

        // Zdjęcia „w działaniu" — kolaż z osobnej galerii strony wsparcia
        // (dowód, że realnie działamy). Do 7 zdjęć w mozaikowym układzie.
        $photos = $settings->supportGalleryImages()->take(7);

        // Logotypy zaufania — ci sami partnerzy co na „O organizacji", jeśli
        // admin włączył ich pokazywanie na stronie wsparcia.
        $partners = $settings->support_show_partners
            ? Partner::orderBy('order')->orderBy('name')->get()
            : collect();

        // Trzy ostatnie fakty z organizacji: najnowsze opublikowane aktualności
        // (o ile moduł aktualności jest włączony).
        $newsTtl    = $settings->isModuleEnabled('news') && $settings->cacheEnabled('news')
            ? $settings->cacheTtl('news_item', 3600)
            : 0;
        $latestNews = collect();
        if ($settings->isModuleEnabled('news')) {
            $latestNewsQuery = fn () => News::published()->with('category')->orderByDesc('published_at')->limit(3)->get();
            if ($newsTtl > 0) {
                $latestNews = Cache::remember('news_latest3', $newsTtl, $latestNewsQuery);
                if (! $latestNews instanceof \Illuminate\Database\Eloquent\Collection) {
                    Cache::forget('news_latest3');
                    $latestNews = $latestNewsQuery();
                }
            } else {
                $latestNews = $latestNewsQuery();
            }
        }

        if ($settings->site_template === 'federation') {
            return view('templates.federation.support');
        }

        return view('support.show', compact('stats', 'photos', 'partners', 'latestNews'));
    }
}
