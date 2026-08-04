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

/**
 * Panel admin: zarządzanie aktualnościami z wyszukiwaniem, klonowaniem, operacjami
 * zbiorczymi i obsługą zdjęcia głównego (plik/Unsplash/biblioteka).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), clone(), bulk().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NewsController extends Controller
{
    use HandlesContentApproval;

    /** Wyświetla listę aktualności z wyszukiwaniem, filtrowaniem statusu i kategorii. */
    public function index(Request $request)
    {
        $search = $request->query('q', '');
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
            ->withCount('clones')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($category !== '', fn ($q) => $q->where('news_category_id', $category))
            ->orderBy($col, $dir)
            ->paginate(30)
            ->withQueryString();

        return view('admin.news.index', [
            'news' => $news,
            'categories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'q' => $search,
            'status' => $status,
            'category' => $category,
            'sort' => $sort,
        ]);
    }

    /** Wyświetla formularz tworzenia nowej aktualności. */
    public function create()
    {
        return view('admin.news.form', [
            'news' => new News,
            'newsCategories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'projects' => Project::orderBy('title')->get(),
        ]);
    }

    /** Eksport listy newsów do pliku CSV. */
    public function export()
    {
        $news = News::with('category', 'tags')
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'news_category_id', 'published_at', 'is_published', 'is_archived', 'image_alt', 'excerpt']);

        $rows = [['ID', 'Tytuł', 'Slug', 'Kategoria', 'Tagi', 'Data publikacji', 'Status', 'Alt zdjęcia', 'Excerpt']];
        foreach ($news as $item) {
            $status = $item->is_archived ? 'Zarchiwizowany' : ($item->is_published ? 'Opublikowany' : 'Szkic');
            $rows[] = [
                $item->id,
                $item->title,
                $item->slug,
                $item->category?->name ?? '',
                $item->tags->pluck('name')->implode(', '),
                $item->published_at?->format('Y-m-d H:i') ?? '',
                $status,
                $item->image_alt ?? '',
                $item->excerpt ?? '',
            ];
        }

        $csv = collect($rows)->map(fn ($r) => implode(';', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $r)))->implode("\n");

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="newsy-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /** Sprawdza czy istnieje news o podobnym tytule (AJAX). */
    public function checkDuplicate(Request $request)
    {
        $title = trim($request->query('title', ''));
        if (strlen($title) < 4) {
            return response()->json(['found' => false, 'items' => []]);
        }
        $excludeId = $request->integer('exclude');
        $words = collect(preg_split('/\s+/', mb_strtolower($title)))->filter(fn ($w) => mb_strlen($w) > 3);
        if ($words->isEmpty()) {
            return response()->json(['found' => false, 'items' => []]);
        }
        $q = News::query();
        if ($excludeId) $q->where('id', '!=', $excludeId);
        foreach ($words->take(3) as $w) {
            $q->orWhere('title', 'like', '%' . $w . '%');
        }
        $found = $q->orderByDesc('published_at')->limit(3)->pluck('title');
        return response()->json(['found' => $found->isNotEmpty(), 'items' => $found]);
    }

    /** Zapisuje nową aktualność z tagami i opcjonalnym zdjęciem. */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $news = News::create($data);

        $this->handleImage($request, $news);

        $news->tags()->sync($this->resolveTagIds($request->input('tags', '')));

        return redirect()->route('admin.newsy.index')->with('status', 'News został utworzony.');
    }

    /** Wyświetla formularz edycji aktualności. */
    public function edit(News $news)
    {
        return view('admin.news.form', [
            'news' => $news->load('tags'),
            'newsCategories' => NewsCategory::orderBy('order')->orderBy('name')->get(),
            'projects' => Project::orderBy('title')->get(),
            'clonesCount' => $news->clones()->count(),
        ]);
    }

    /** Aktualizuje aktualność z tagami i opcjonalnym zdjęciem. */
    public function update(Request $request, News $news)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $news->id);

        $news->update($data);

        $this->handleImage($request, $news);

        $news->tags()->sync($this->resolveTagIds($request->input('tags', '')));

        return redirect()->route('admin.newsy.index')->with('status', 'News został zaktualizowany.');
    }

    /** Usuwa aktualność (opcjonalnie wraz z klonami). */
    public function destroy(Request $request, News $news)
    {
        if ($request->boolean('with_clones')) {
            $news->clones()->each(fn ($clone) => $clone->delete());
        }

        $news->delete();

        return redirect()->route('admin.newsy.index')->with('status', 'News został usunięty.');
    }

    /** Klonuje aktualność jako szkic bez zdjęcia. */
    public function clone(News $news)
    {
        $clone = $news->replicate();
        $clone->title = "{$news->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->is_featured = false;
        $clone->is_clone = true;
        $clone->cloned_from_id = $news->id;
        $clone->save();

        // Kopiujemy tagi; zdjęcie (media) trzeba wgrać ponownie — jak przy klonowaniu stron.
        $clone->tags()->sync($news->tags->pluck('id')->all());

        return redirect()->route('admin.newsy.edit', $clone)
            ->with('status', "News został sklonowany jako „{$clone->title}”. Jest zapisany jako szkic (bez zdjęcia — dodaj je ponownie).");
    }

    /** Wykonuje zbiorczą operację (kosz / publikuj / cofnij / archiwizuj) na zaznaczonych aktualnościach. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:trash,publish,unpublish,archive'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $news = News::withTrashed()->whereIn('id', $data['ids'])->get();

        if ($news->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono newsów do przetworzenia.');
        }

        $count = $news->count();

        match ($data['action']) {
            'trash' => $news->each->delete(),
            'publish' => News::whereIn('id', $news->pluck('id'))->update(['is_published' => true]),
            'unpublish' => News::whereIn('id', $news->pluck('id'))->update(['is_published' => false]),
            'archive' => News::whereIn('id', $news->pluck('id'))->update(['is_archived' => true]),
        };

        $message = match ($data['action']) {
            'trash' => "Przeniesiono do kosza newsów: {$count}.",
            'publish' => "Opublikowano newsów: {$count}.",
            'unpublish' => "Cofnięto publikację newsów: {$count}.",
            'archive' => "Zarchiwizowano newsów: {$count}.",
        };

        return redirect()->back()->with('status', $message);
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
            'image_focal_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'image_focal_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'article_layout' => ['nullable', Rule::in(['default', 'side', 'wide', 'none'])],
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
        $data['image_focal_x'] = (int) ($data['image_focal_x'] ?? 50);
        $data['image_focal_y'] = (int) ($data['image_focal_y'] ?? 50);
        $data['article_layout'] = $data['article_layout'] ?? 'default';
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_archived'] = $request->boolean('is_archived');
        $data['is_clone'] = $request->boolean('is_clone');
        unset($data['image']);

        return $this->applyApprovalWorkflow($data);
    }

    /**
     * Set the news image from either an uploaded file or a chosen Unsplash
     * photo (downloaded server-side). A file upload takes precedence.
     * Uploading a new file replaces the existing one (singleFile collection).
     * Sending delete_image=1 without a new file removes the current photo.
     */
    private function handleImage(Request $request, News $news): void
    {
        if ($request->hasFile('image')) {
            $news->addMediaFromRequest('image')->toMediaCollection('image');
            $news->refreshImageDimensions();

            return;
        }

        if ($request->boolean('delete_image')) {
            $news->clearMediaCollection('image');
            $news->update(['image_alt' => null]);

            return;
        }

        if ($request->filled('library_media_id')) {
            $source = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($request->integer('library_media_id'));

            if ($source) {
                $source->copy($news, 'image');
                $news->refreshImageDimensions();

                if (blank($news->image_alt) && $request->filled('library_alt')) {
                    $news->update(['image_alt' => $request->input('library_alt')]);
                }
            }

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

        // withTrashed() – slug w koszu też blokuje constraint UNIQUE w bazie.
        while (News::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
