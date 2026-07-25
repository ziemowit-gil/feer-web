<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;

class BlogController extends Controller
{
    public function index()
    {
        $articles = BlogArticle::published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(9);

        return view('blog.index', compact('articles'));
    }

    public function show(BlogArticle $article)
    {
        abort_unless($article->isVisible(), 404);

        $article->load('approvedComments');

        return view('blog.show', compact('article'));
    }
}
