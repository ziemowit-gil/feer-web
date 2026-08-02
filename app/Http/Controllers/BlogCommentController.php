<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use Illuminate\Http\Request;

/**
 * Przyjmuje komentarze do artykułów bloga Wiem FEER; trafiają do moderacji (is_approved=false).
 *
 * Metody: store().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlogCommentController extends Controller
{
    /** Waliduje i zapisuje komentarz do artykułu bloga; nowe komentarze czekają na moderację. */
    public function store(Request $request, BlogArticle $article)
    {
        abort_unless($article->isVisible(), 404);

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:3000'],
            'website' => ['prohibited'],
        ], [
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        // New comments wait for moderation before appearing publicly.
        $article->comments()->create([
            'author_name' => $data['author_name'],
            'email' => $data['email'] ?? null,
            'body' => $data['body'],
            'is_approved' => false,
        ]);

        return redirect()
            ->route('blog.show', $article)
            ->withFragment('komentarze')
            ->with('comment_status', 'Dziękujemy! Twój komentarz czeka na zatwierdzenie przez moderatora.');
    }
}
