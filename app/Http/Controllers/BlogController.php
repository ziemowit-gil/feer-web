<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use Illuminate\Http\Request;

/**
 * Publiczna lista i widok artykułów bloga Wiem FEER (z obsługą podglądu szkicu).
 *
 * Metody: index(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlogController extends Controller
{
    /** Wyświetla listing artykułów bloga z wyróżnionym najnowszym wpisem w sekcji hero. */
    public function index()
    {
        // Najnowszy wpis prezentujemy jako wyróżniony (hero) na pierwszej stronie.
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

        return view('blog.index', compact('articles', 'featured'));
    }

    /** Wyświetla pojedynczy artykuł bloga; obsługuje tryb podglądu szkicu dla zalogowanych. */
    public function show(Request $request, BlogArticle $article)
    {
        $preview = $this->isPreviewRequest($request);
        abort_unless($article->isVisible() || $preview, 404);

        $article->load('approvedComments');

        return view('blog.show', compact('article', 'preview'));
    }
}
