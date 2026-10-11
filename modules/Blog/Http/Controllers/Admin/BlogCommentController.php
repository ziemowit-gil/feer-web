<?php

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Blog\Models\BlogComment;

class BlogCommentController extends Controller
{
    public function index()
    {
        $pending  = BlogComment::with('article')->pending()->orderByDesc('created_at')->get();
        $approved = BlogComment::with(['article', 'replies'])->approved()->whereNull('parent_id')
            ->orderByDesc('created_at')->limit(100)->get();

        return view('blog::admin.comments.index', compact('pending', 'approved'));
    }

    public function approve(BlogComment $comment)
    {
        $comment->update(['is_approved' => true]);

        return back()->with('status', 'Komentarz został zatwierdzony.');
    }

    /**
     * Odpowiedź redakcji: od razu zatwierdzona, podpisana „Odpowiedź redakcji".
     * Odpowiedź na komentarz oczekujący zatwierdza też ten komentarz — inaczej
     * odpowiedź wisiałaby pod niewidocznym wpisem.
     */
    public function reply(Request $request, BlogComment $comment)
    {
        $data = $request->validate([
            'reply_body' => ['required', 'string', 'max:3000'],
        ], [], ['reply_body' => 'treść odpowiedzi']);

        $parent = $comment->parent_id ? ($comment->parent ?? $comment) : $comment;

        if (! $parent->is_approved) {
            $parent->update(['is_approved' => true]);
        }

        BlogComment::create([
            'article_id'     => $parent->article_id,
            'parent_id'      => $parent->id,
            'author_name'    => $request->user()?->name ?: 'Redakcja Wiem FEER',
            'email'          => null,
            'body'           => $data['reply_body'],
            'is_approved'    => true,
            'is_staff_reply' => true,
            'user_id'        => $request->user()?->id,
        ]);

        return back()->with('status', 'Odpowiedź redakcji została opublikowana.');
    }

    public function destroy(BlogComment $comment)
    {
        // Odpowiedzi nie mają klucza obcego do komentarza — usuwamy je razem z nim.
        BlogComment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        return back()->with('status', 'Komentarz został usunięty.');
    }
}
