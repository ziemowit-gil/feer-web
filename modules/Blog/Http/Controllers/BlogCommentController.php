<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogComment;
use Modules\Blog\Notifications\BlogCommentAwaitingModeration;

class BlogCommentController extends Controller
{
    private const THANKS = 'Dziękujemy! Twój komentarz czeka na zatwierdzenie przez moderatora.';

    public function store(Request $request, BlogArticle $article)
    {
        abort_unless($article->isVisible() && ! $article->showsPlaceholder(), 404);

        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:100'],
            'email'       => ['nullable', 'email', 'max:255'],
            'body'        => ['required', 'string', 'max:3000'],
        ]);

        // Ochrona antyspamowa (pułapka, żeton czasu, zadanie tekstowe, analiza treści).
        if ($spam = SpamGuard::inspect($request, $data, 'blog-komentarz:' . $article->id)) {
            Log::warning('Zablokowano komentarz Wiem FEER jako spam', [
                'artykuł' => $article->slug,
                'powód'   => $spam['reason'],
                'ip'      => $request->ip(),
            ]);

            // Bot dostaje zwykłe potwierdzenie — nie wie, że go odfiltrowano.
            return $spam['silent']
                ? redirect()->route('blog.show', $article)->withFragment('komentarze')->with('comment_status', self::THANKS)
                : redirect()->route('blog.show', $article)->withFragment('dodaj-komentarz')
                    ->withErrors(['spam' => $spam['message']])->withInput();
        }

        $comment = $article->comments()->create([
            'author_name' => $data['author_name'],
            'email'       => $data['email'] ?? null,
            'body'        => $data['body'],
            'is_approved' => false,
        ]);

        $this->notifyModerators($comment);

        return redirect()
            ->route('blog.show', $article)
            ->withFragment('komentarze')
            ->with('comment_status', self::THANKS);
    }

    /** Powiadomienie e-mail do osób, które mogą zatwierdzać treść i mają dostęp do bloga. */
    private function notifyModerators(BlogComment $comment): void
    {
        $recipients = User::approvers()
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user) => $user->canAccessModule('blog'));

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new BlogCommentAwaitingModeration($comment));
        } catch (\Throwable $e) {
            // Awaria poczty nie może blokować dodania komentarza.
            report($e);
        }
    }
}
