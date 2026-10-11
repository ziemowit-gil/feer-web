<?php

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogTag;
use Modules\Blog\Support\BlogImages;

class BlogArticleController extends Controller
{
    /** Katalog zdjęć wyróżniających na dysku `public`. */
    private const IMAGE_DIR = 'wiem-feer';

    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $status = array_key_exists($status, BlogArticle::STATUSES) ? $status : '';

        $articles = BlogArticle::with(['category', 'author', 'coAuthors'])
            ->withCount([
                'comments',
                'comments as pending_comments_count' => fn ($q) => $q->where('is_approved', false),
            ])
            ->when($status !== '', fn ($q) => $q->status($status))
            ->orderByDesc('created_at')
            ->get();

        return view('blog::admin.index', compact('articles', 'status'));
    }

    public function create()
    {
        return view('blog::admin.form', $this->formData(new BlogArticle));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $article = new BlogArticle;
        $this->applyImage($request, $article, $data);
        $article->fill($data)->save();
        $article->tags()->sync(BlogTag::idsFromList($request->input('tags')));
        $this->syncCoAuthors($request, $article);

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został utworzony.');
    }

    public function edit(BlogArticle $article)
    {
        return view('blog::admin.form', $this->formData($article));
    }

    public function update(Request $request, BlogArticle $article)
    {
        $data = $this->validated($request, $article);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $article->id);

        $this->applyImage($request, $article, $data);
        $article->update($data);
        $article->tags()->sync(BlogTag::idsFromList($request->input('tags')));
        $this->syncCoAuthors($request, $article);

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został zaktualizowany.');
    }

    public function destroy(BlogArticle $article)
    {
        $this->deleteImageFile($article->image_path, $article->id);
        $article->delete();

        return redirect()->route('admin.wiem-feer.index')->with('status', 'Artykuł został usunięty.');
    }

    public function clone(BlogArticle $article)
    {
        $clone = $article->replicate();
        $clone->title = "{$article->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->is_disabled = true;
        $clone->is_featured = false;
        $clone->published_at = null;
        $clone->save();
        $clone->tags()->sync($article->tags()->pluck('blog_tags.id'));
        $clone->coAuthors()->sync($article->coAuthors->mapWithKeys(fn ($a) => [$a->id => ['position' => $a->pivot->position]])->all());

        return redirect()->route('admin.wiem-feer.edit', $clone)
            ->with('status', 'Artykul zostal sklonowany jako: ' . $clone->title . '. Jest zapisany jako wylaczony.');
    }

    public function toggleDisabled(BlogArticle $article)
    {
        $article->update(['is_disabled' => ! $article->is_disabled]);

        $message = $article->is_disabled
            ? 'Artykuł „' . $article->title . '" został wyłączony.'
            : 'Artykuł „' . $article->title . '" został ponownie włączony.';

        return redirect()->route('admin.wiem-feer.index')->with('status', $message);
    }

    /** @return array<string, mixed> */
    private function formData(BlogArticle $article): array
    {
        // Nowy wpis: domyślnie autor powiązany z zalogowanym użytkownikiem.
        if (! $article->exists && ! $article->author_id && auth()->id()) {
            $article->author_id = BlogAuthor::active()->where('user_id', auth()->id())->value('id');
        }

        $selectedCo = $article->exists ? $article->coAuthors->pluck('id')->all() : [];

        return [
            'authors'      => BlogAuthor::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', array_filter([$article->author_id, ...$selectedCo])))
                ->orderBy('name')->get(),
            'coAuthorIds'  => $selectedCo,
            'article'      => $article,
            'categories'   => BlogCategory::orderBy('sort_order')->orderBy('name')->get(),
            'tagsValue'    => $article->exists ? $article->tags->pluck('name')->join(', ') : '',
            'tagsAll'      => BlogTag::orderBy('name')->pluck('name'),
        ];
    }

    private function validated(Request $request, ?BlogArticle $article = null): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'author_name'      => ['nullable', 'string', 'max:255'],
            'author_bio'       => ['nullable', 'string', 'max:600'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'body'             => ['required', 'string'],
            'published_at'     => ['nullable', 'date'],
            'disabled_message' => ['nullable', 'string', 'max:2000'],
            'wip_mode'         => ['nullable', Rule::in(array_keys(BlogArticle::WIP_MODES))],
            'wip_message'      => ['nullable', 'string', 'max:2000'],
            'category_id'      => ['nullable', 'integer', Rule::exists('blog.blog_categories', 'id')],
            'author_id'        => ['nullable', 'integer', Rule::exists('blog.blog_authors', 'id')],
            'co_authors'       => ['nullable', 'array', 'max:10'],
            'co_authors.*'     => ['integer', Rule::exists('blog.blog_authors', 'id')],
            'tags'             => ['nullable', 'string', 'max:1000'],
            'image'            => ['nullable', 'image', 'max:5120'],
            'library_media_id' => ['nullable', 'integer'],
            'image_alt'        => ['nullable', 'string', 'max:255'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ], [], [
            'image'            => 'zdjęcie',
            'image_alt'        => 'tekst alternatywny zdjęcia',
            'category_id'      => 'kategoria',
            'author_id'        => 'autor',
            'co_authors'       => 'współautorzy',
            'meta_title'       => 'tytuł SEO',
            'meta_description' => 'opis SEO',
            'author_bio'       => 'nota o autorze',
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['is_disabled'] = $request->boolean('is_disabled');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['disabled_message'] = trim((string) ($data['disabled_message'] ?? '')) ?: null;
        $data['wip_mode'] = $data['wip_mode'] ?? null;
        $data['wip_message'] = $data['wip_mode']
            ? (trim((string) ($data['wip_message'] ?? '')) ?: null)
            : null;
        $data['author_bio'] = trim((string) ($data['author_bio'] ?? '')) ?: null;
        $data['meta_title'] = trim((string) ($data['meta_title'] ?? '')) ?: null;
        $data['meta_description'] = trim((string) ($data['meta_description'] ?? '')) ?: null;
        $data['category_id'] = $data['category_id'] ?? null;
        $data['author_id'] = $data['author_id'] ?? null;
        $data['image_alt'] = trim((string) ($data['image_alt'] ?? '')) ?: null;
        $data['image_decorative'] = $request->boolean('image_decorative');

        // Zdjęcie po zapisie: nowe (plik / biblioteka), dotychczasowe albo żadne.
        $willHaveImage = $request->hasFile('image')
            || $request->filled('library_media_id')
            || ($article?->hasImage() && ! $request->boolean('remove_image'));

        // WCAG 1.1.1: zdjęcie musi mieć opis albo świadome oznaczenie jako dekoracyjne.
        if ($willHaveImage && ! $data['image_decorative'] && $data['image_alt'] === null) {
            throw ValidationException::withMessages([
                'image_alt' => 'Opisz zdjęcie (tekst alternatywny) albo zaznacz, że jest dekoracyjne.',
            ]);
        }

        if ($data['image_decorative']) {
            $data['image_alt'] = null;
        }

        unset($data['tags'], $data['image'], $data['library_media_id'], $data['co_authors']);

        return $data;
    }

    /**
     * Zdjęcie wyróżniające: wgrany plik ma pierwszeństwo, potem zdjęcie z biblioteki
     * multimediów (kopiowane na dysk `public`, żeby nie zniknęło razem z oryginałem),
     * a „Usuń zdjęcie" czyści ścieżkę. Plik zastąpionego zdjęcia jest usuwany.
     */
    private function applyImage(Request $request, BlogArticle $article, array &$data): void
    {
        $old = $article->image_path;
        $new = null;

        if ($request->hasFile('image')) {
            $new = BlogImages::storeUpload($request->file('image'), self::IMAGE_DIR);
        } elseif ($request->filled('library_media_id')) {
            $new = BlogImages::copyFromLibrary($request->integer('library_media_id'), self::IMAGE_DIR);
            if ($new === null) {
                throw ValidationException::withMessages(['image' => 'Nie udało się skopiować zdjęcia z biblioteki multimediów.']);
            }
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
            $data['image_alt'] = null;
            $data['image_decorative'] = false;
            $this->deleteImageFile($old, $article->id);

            return;
        }

        if ($new !== null) {
            $data['image_path'] = $new;
            $this->deleteImageFile($old, $article->id);
        }
    }

    /** Współautorzy w kolejności zaznaczenia, bez autora głównego. */
    private function syncCoAuthors(Request $request, BlogArticle $article): void
    {
        $ids = collect((array) $request->input('co_authors', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $article->author_id)
            ->unique()
            ->values();

        $article->coAuthors()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
    }

    /** Usuwa plik zdjęcia, o ile nie używa go inny wpis (np. klon). */
    private function deleteImageFile(?string $path, ?int $articleId): void
    {
        if (! $path || BlogArticle::where('image_path', $path)->when($articleId, fn ($q) => $q->whereKeyNot($articleId))->exists()) {
            return;
        }

        BlogImages::delete($path);
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
