<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

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
        $categories = NewsCategory::orderBy('order')->orderBy('name')->get();
        $activeCategory = $request->query('kategoria')
            ? $categories->firstWhere('slug', $request->query('kategoria'))
            : null;

        $news = News::published()->with(['category', 'tags'])
            ->when($activeCategory, fn ($q) => $q->where('news_category_id', $activeCategory->id))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('news.index', compact('news', 'categories', 'activeCategory'));
    }

    /** Wyświetla stronę szczegółów aktualności; obsługuje tryb podglądu szkicu. */
    public function show(Request $request, News $news)
    {
        $preview = $this->isPreviewRequest($request);
        abort_unless(($news->is_published && $news->published_at <= now()) || $preview, 404);
        $news->load(['category', 'tags', 'etr']);

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $news->accent_color ?: SiteSetting::current()->audienceColor($news->audience);

        return view('news.show', compact('news', 'brandColor', 'preview'));
    }

    /** Wyświetla widok do wydruku (PDF) opublikowanej aktualności — konwertowany przez headless Chromium. */
    public function pdf(News $news)
    {
        abort_unless($news->is_published && $news->published_at <= now(), 404);
        $news->load(['category']);
        $siteSettings = SiteSetting::current();
        $brandPalette = $siteSettings->brandPalette();
        $printedAt = now()->format('d.m.Y');

        return view('news.pdf', compact('news', 'siteSettings', 'brandPalette', 'printedAt'));
    }
}
