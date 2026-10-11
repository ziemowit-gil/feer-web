<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

/**
 * Publiczna lista i szczegóły aktualności oraz widok do wydruku (generowany przez headless Chromium).
 *
 * Metody: index(), show(), pdf().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NewsController extends Controller
{
    /** Wyświetla listing aktualności z opcjonalnym filtrem kategorii i paginacją. */
    public function index(Request $request)
    {
        $settings = \App\Models\SiteSetting::current();
        $categoriesTtl = $settings->cacheEnabled('news') ? $settings->cacheTtl('news_categories', 86400) : 0;
        if ($categoriesTtl > 0) {
            try {
                $cached = Cache::get('news_categories');
                if ($cached instanceof \Illuminate\Database\Eloquent\Collection) {
                    $categories = $cached;
                } else {
                    if ($cached !== null) {
                        Cache::forget('news_categories');
                    }
                    $categories = NewsCategory::orderBy('order')->orderBy('name')->get();
                    Cache::put('news_categories', $categories, $categoriesTtl);
                }
            } catch (\Throwable) {
                Cache::forget('news_categories');
                $categories = NewsCategory::orderBy('order')->orderBy('name')->get();
            }
        } else {
            $categories = NewsCategory::orderBy('order')->orderBy('name')->get();
        }
        $activeCategory = $request->query('kategoria')
            ? $categories->firstWhere('slug', $request->query('kategoria'))
            : null;

        $news = News::published()->forCurrentSite()->with(['category', 'tags'])
            ->when($activeCategory, fn ($q) => $q->where('news_category_id', $activeCategory->id))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        // Szablon FEER ma własny układ listy aktualności (wyróżniony wpis + płaskie karty).
        if ($settings->site_template === 'feer') {
            return view('news.index-feer', compact('news', 'categories', 'activeCategory'));
        }

        return view('news.index', compact('news', 'categories', 'activeCategory'));
    }

    /** Wyświetla stronę szczegółów aktualności; obsługuje tryb podglądu szkicu. */
    public function show(Request $request, News $news)
    {
        $preview = $this->isPreviewRequest($request);
        abort_unless(($news->is_published && $news->published_at <= now()) || $preview, 404);
        $news->load(['category', 'tags', 'etr', 'project']);

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $news->accent_color ?: SiteSetting::current()->audienceColor($news->audience);

        // Układ „portal": kolumna boczna z najnowszymi wpisami działu + „Zobacz także" (4 karty).
        $isPortal = ($news->article_layout ?? 'default') === 'portal';
        $categoryNews = $isPortal ? $this->categoryNews($news, 6) : collect();
        $isFeer = SiteSetting::current()->site_template === 'feer';

        if ($isPortal) {
            $related = $this->relatedNews($news, 4, $categoryNews->pluck('id')->all());
        } elseif ($isFeer) {
            $related = News::published()->forCurrentSite()->with('category')->where('id', '!=', $news->id)->orderByDesc('published_at')->limit(3)->get();
        } else {
            $related = collect();
        }

        // Szablon FEER ma własny widok wpisu (nagłówek z zajawką, narzędzia artykułu z boku).
        $view = $isFeer ? 'news.show-feer' : 'news.show';

        return view($view, compact('news', 'brandColor', 'preview', 'related', 'categoryNews'));
    }

    /** Najnowsze inne opublikowane wpisy z tej samej kategorii (kolumna boczna układu „portal"). */
    private function categoryNews(News $news, int $limit)
    {
        if (! $news->news_category_id) {
            return collect();
        }

        return News::published()->forCurrentSite()
            ->where('news_category_id', $news->news_category_id)
            ->where('id', '!=', $news->id)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * „Zobacz także": najpierw wpisy z tej samej kategorii, potem najnowsze pozostałe.
     * Pomija bieżący wpis i te już widoczne w kolumnie bocznej.
     */
    private function relatedNews(News $news, int $limit, array $exclude = [])
    {
        return News::published()->forCurrentSite()->with('category')
            ->whereNotIn('id', array_merge([$news->id], $exclude))
            ->when($news->news_category_id, fn ($q) => $q->orderByRaw(
                'CASE WHEN news_category_id = ? THEN 0 ELSE 1 END', [$news->news_category_id]
            ))
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Generuje PDF opublikowanej aktualności.
     * Silnik: Browsershot (headless Chromium) z fallbackiem do DomPDF.
     */
    public function pdf(News $news): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($news->is_published && $news->published_at <= now(), 404);
        $news->load(['category']);
        $siteSettings = SiteSetting::current();
        $brandPalette  = $siteSettings->brandPalette();
        $printedAt     = now()->format('d.m.Y');
        $filename      = Str::slug($news->title) . '.pdf';

        try {
            $shot = Browsershot::html(
                view('news.pdf', compact('news', 'siteSettings', 'brandPalette', 'printedAt'))->render()
            )
                ->format('A4')
                ->margins(20, 25, 20, 25)
                ->waitUntilNetworkIdle();

            if ($path = config('services.browsershot.chrome_path')) {
                $shot->setChromePath($path);
            }
            if (config('services.browsershot.no_sandbox')) {
                $shot->noSandbox();
            }

            return response($shot->pdf())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Throwable) {
            return Pdf::loadView('news.pdf-print', compact('news', 'siteSettings', 'brandPalette', 'printedAt'))
                ->setPaper('a4')
                ->download($filename);
        }
    }
}
