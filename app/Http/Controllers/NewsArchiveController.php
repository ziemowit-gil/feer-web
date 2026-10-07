<?php

namespace App\Http\Controllers;

use App\Models\News;

class NewsArchiveController extends Controller
{
    public function index()
    {
        // Treści archiwalne — opublikowane natywnie w nowym CMS, potem zarchiwizowane
        $archived = News::where('is_archived', true)
            ->where('is_legacy', false)
            ->where('is_published', true)
            ->with(['category'])
            ->orderByDesc('published_at')
            ->paginate(12, ['*'], 'arch')
            ->withQueryString();

        // Treści ze starej strony — zaimportowane z feer-demo.2clicks.pl
        $legacy = News::where('is_legacy', true)
            ->where('is_published', true)
            ->with(['category'])
            ->orderByDesc('published_at')
            ->paginate(12, ['*'], 'leg')
            ->withQueryString();

        // Szablon FEER ma własny układ archiwum (przyciski zakresu + płaskie wiersze).
        $view = \App\Models\SiteSetting::current()->site_template === 'feer' ? 'news.archiwum-feer' : 'news.archiwum';

        return view($view, compact('archived', 'legacy'));
    }
}
