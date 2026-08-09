<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Blog\Models\BlogArticle;
use Illuminate\Http\Request;

class BlogCommentController extends Controller
{
    public function store(Request $request, BlogArticle $article)
    {
        abort_unless($article->isVisible(), 404);

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'email'       => ['nullable', 'email', 'max:255'],
            'body'        => ['required', 'string', 'max:3000'],
            'website'     => ['prohibited'],
        ], [
            'website.prohibited' => 'Wykryto nieprawidłowe zgłoszenie.',
        ]);

        $article->comments()->create([
            'author_name' => $data['author_name'],
            'email'       => $data['email'] ?? null,
            'body'        => $data['body'],
            'is_approved' => false,
        ]);

        return redirect()
            ->route('blog.show', $article)
            ->withFragment('komentarze')
            ->with('comment_status', 'Dziękujemy! Twój komentarz czeka na zatwierdzenie przez moderatora.');
    }
}
