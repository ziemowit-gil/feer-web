<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Blog\Models\BlogArticle;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $featured = BlogArticle::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->first();

        $query = BlogArticle::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($featured) {
            $query->whereKeyNot($featured->getKey());
        }

        $articles = $query->paginate(8)->withQueryString();

        return view('blog::index', compact('articles', 'featured'));
    }

    public function show(Request $request, BlogArticle $article)
    {
        $preview = $this->isPreviewRequest($request);
        abort_unless($article->isVisible() || $preview, 404);

        $article->load('approvedComments');

        return view('blog::show', compact('article', 'preview'));
    }
}
