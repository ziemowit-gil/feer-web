<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class NewsController extends Controller
{
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

    public function show(News $news)
    {
        abort_unless($news->is_published && $news->published_at <= now(), 404);
        $news->load(['category', 'tags', 'etr']);

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $news->accent_color ?: SiteSetting::current()->audienceColor($news->audience);

        return view('news.show', compact('news', 'brandColor'));
    }
}
