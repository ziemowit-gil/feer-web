<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\SiteSetting;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::published()->with(['category', 'tags'])->orderByDesc('published_at')->paginate(9);

        return view('news.index', compact('news'));
    }

    public function show(News $news)
    {
        abort_unless($news->is_published && $news->published_at <= now(), 404);
        $news->load(['category', 'tags']);

        $brandColor = SiteSetting::current()->audienceColor($news->audience);

        return view('news.show', compact('news', 'brandColor'));
    }
}
