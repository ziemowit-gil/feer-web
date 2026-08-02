<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;

/**
 * Panel admin: moderacja komentarzy bloga — zatwierdzanie oczekujących i usuwanie.
 *
 * Metody: index(), approve(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlogCommentController extends Controller
{
    /** Wyświetla listę komentarzy do moderacji (oczekujące i ostatnie zatwierdzone). */
    public function index()
    {
        $pending = BlogComment::with('article')->pending()->orderByDesc('created_at')->get();
        $approved = BlogComment::with('article')->approved()->orderByDesc('created_at')->limit(100)->get();

        return view('admin.blog-comments.index', compact('pending', 'approved'));
    }

    /** Zatwierdza komentarz i czyni go widocznym na blogu. */
    public function approve(BlogComment $comment)
    {
        $comment->update(['is_approved' => true]);

        return back()->with('status', 'Komentarz został zatwierdzony.');
    }

    /** Usuwa komentarz. */
    public function destroy(BlogComment $comment)
    {
        $comment->delete();

        return back()->with('status', 'Komentarz został usunięty.');
    }
}
