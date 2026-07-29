<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesContentApproval;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    use HandlesContentApproval;

    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $category = $request->query('category', '');
        $sort = $request->query('sort', 'date_desc');

        [$col, $dir] = match ($sort) {
            'title_asc' => ['title', 'asc'],
            'title_desc' => ['title', 'desc'],
            'date_asc' => ['published_at', 'asc'],
            default => ['published_at', 'desc'],
        };

        $news = News::with(['category', 'tags'])
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($category !== '', fn ($q) => $q->where('news_category_id', $category))
            ->orderBy($col, $dir)
            ->get();

        return view('admin.news.index', [
            'news' => $news,
            'categories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'status' => $status,
            'category' => $category,
            'sort' => $sort,
        ]);
    }

    public function create()
    {
        return view('admin.news.form', [
            'news' => new News,
            'newsCategories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'projects' => Project::orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $news = News::create($data);

        $this->handleImage($request, $news);

        $news->tags()->sync($this->resolveTagIds($request->input('tags', '')));

        return redirect()->route('admin.newsy.index')->with('status', 'News został utworzony.');
    }

    public function edit(News $news)
    {
        return view('admin.news.form', [
            'news' => $news->load('tags'),
            'newsCategories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'projects' => Project::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, News $news)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $news->id);

        $news->update($data);

        $this->handleImage($request, $news);

        $news->tags()->sync($this->resolveTagIds($request->input('tags', '')));

        return redirect()->route('admin.newsy.index')->with('status', 'News został zaktualizowany.');
    }

    public function destroy(News $news)
    {
        $news->delete();

        return redirect()->route('admin.newsy.index')->with('status', 'News został usunięty.');
    }

    public function clone(News $news)
    {
        $clone = $news->replicate();
        $clone->title = "{$news->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->is_featured = false;
        $clone->save();

        // Kopiujemy tagi; zdjęcie (media) trzeba wgrać ponownie — jak przy klonowaniu stron.
        $clone->tags()->sync($news->tags->pluck('id')->all());

        return redirect()->route('admin.newsy.edit', $clone)
            ->with('status', "News został sklonowany jako „{$clone->title}”. Jest zapisany jako szkic (bez zdjęcia — dodaj je ponownie).");
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'news_category_id' => ['nullable', 'exists:news_categories,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', Rule::in(array_keys(SiteSetting::current()->audienceOptions()))],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'published_at' => ['required', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['audience'] = $data['audience'] ?? 'brand';
        // Własny kolor akcentu pilnujemy pod kątem kontrastu WCAG (jak brand/NGO).
        $data['accent_color'] = filled($data['accent_color'] ?? null)
            ? SiteSetting::current()->contrastSafeColor($data['accent_color'])
            : null;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_archived'] = $request->boolean('is_archived');
        unset($data['image']);

        return $this->applyApprovalWorkflow($data);
    }

    /**
     * Set the news image from either an uploaded file or a chosen Unsplash
     * photo (downloaded server-side). A file upload takes precedence.
     */
    private function handleImage(Request $request, News $news): void
    {
        if ($request->hasFile('image')) {
            $news->addMediaFromRequest('image')->toMediaCollection('image');
            $news->refreshImageDimensions();

            return;
        }

        if (! $request->filled('unsplash_full_url')) {
            return;
        }

        $data = $request->validate([
            'unsplash_full_url' => ['required', 'url'],
            'unsplash_download_location' => ['nullable', 'url'],
            'unsplash_author' => ['nullable', 'string', 'max:255'],
            'unsplash_alt' => ['nullable', 'string', 'max:255'],
        ]);

        $accessKey = config('services.unsplash.access_key');

        // Unsplash's API guidelines require pinging download_location on use.
        if ($accessKey && ! empty($data['unsplash_download_location'])) {
            Http::withHeaders(['Authorization' => "Client-ID {$accessKey}"])->get($data['unsplash_download_location']);
        }

        $news->addMediaFromUrl($data['unsplash_full_url'])
            ->usingFileName(Str::random(20).'.jpg')
            ->withCustomProperties(['unsplash_author' => $data['unsplash_author'] ?? null])
            ->toMediaCollection('image');

        $news->refreshImageDimensions();

        // Fall back to the Unsplash description for alt text if none was given.
        if (blank($news->image_alt) && ! blank($data['unsplash_alt'] ?? null)) {
            $news->update(['image_alt' => $data['unsplash_alt']]);
        }
    }

    private function resolveTagIds(?string $tagsInput): array
    {
        $names = array_filter(array_map('trim', explode(',', (string) $tagsInput)));

        return collect($names)->map(function ($name) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );

            return $tag->id;
        })->all();
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'news';
        $slug = $base;
        $suffix = 2;

        while (News::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
