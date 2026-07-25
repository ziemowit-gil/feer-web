<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;

class BlogCommentController extends Controller
{
    public function index()
    {
        $pending = BlogComment::with('article')->pending()->orderByDesc('created_at')->get();
        $approved = BlogComment::with('article')->approved()->orderByDesc('created_at')->limit(100)->get();

        return view('admin.blog-comments.index', compact('pending', 'approved'));
    }

    public function approve(BlogComment $comment)
    {
        $comment->update(['is_approved' => true]);

        return back()->with('status', 'Komentarz został zatwierdzony.');
    }

    public function destroy(BlogComment $comment)
    {
        $comment->delete();

        return back()->with('status', 'Komentarz został usunięty.');
    }
}
