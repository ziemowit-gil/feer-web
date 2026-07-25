<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogArticleController extends Controller
{
    public function index()
    {
        $articles = BlogArticle::withCount([
            'comments',
            'comments as pending_comments_count' => fn ($q) => $q->where('is_approved', false),
        ])->orderByDesc('created_at')->get();

        return view('admin.blog.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.blog.form', ['article' => new BlogArticle]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        BlogArticle::create($data);

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został utworzony.');
    }

    public function edit(BlogArticle $article)
    {
        return view('admin.blog.form', compact('article'));
    }

    public function update(Request $request, BlogArticle $article)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $article->id);

        $article->update($data);

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został zaktualizowany.');
    }

    public function destroy(BlogArticle $article)
    {
        $article->delete();

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został usunięty.');
    }

    public function toggleDisabled(BlogArticle $article)
    {
        $article->update(['is_disabled' => ! $article->is_disabled]);

        $message = $article->is_disabled
            ? "Artykuł „{$article->title}” został wyłączony."
            : "Artykuł „{$article->title}” został ponownie włączony.";

        return redirect()->route('admin.wiem-feer.index')->with('status', $message);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'disabled_message' => ['nullable', 'string', 'max:2000'],
            'wip_mode' => ['nullable', Rule::in(array_keys(BlogArticle::WIP_MODES))],
            'wip_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);

        // Availability controls: "disable article" and "under construction" mode.
        $data['is_disabled'] = $request->boolean('is_disabled');
        $data['disabled_message'] = trim((string) ($data['disabled_message'] ?? '')) ?: null;
        $data['wip_mode'] = $data['wip_mode'] ?? null;
        // A message without a mode selected would never surface — drop it.
        $data['wip_message'] = $data['wip_mode']
            ? (trim((string) ($data['wip_message'] ?? '')) ?: null)
            : null;

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'artykul';
        $slug = $base;
        $suffix = 2;

        while (BlogArticle::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
