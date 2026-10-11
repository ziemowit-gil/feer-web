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
use Modules\Blog\Support\ArticleListFilters;
use Modules\Blog\Support\BlogImages;
use Modules\Blog\Support\HtmlToMarkdown;
use Modules\Blog\Support\MarkdownRenderer;

class BlogArticleController extends Controller
{
    /** Katalog zdjęć wyróżniających na dysku `public`. */
    private const IMAGE_DIR = 'wiem-feer';

    /** Akcje zbiorcze listy: klucz → opis w komunikacie. */
    public const BULK_ACTIONS = [
        'publish'  => 'Opublikowano',
        'draft'    => 'Cofnięto do szkicu',
        'disable'  => 'Wyłączono',
        'enable'   => 'Włączono',
        'category' => 'Przypisano kategorię',
        'delete'   => 'Usunięto',
    ];

    /** Limit długości treści Markdown/HTML (znaki). */
    private const MARKDOWN_MAX = 500000;

    public function index(Request $request)
    {
        $filters = new ArticleListFilters($request);

        $articles = $filters->apply(
            BlogArticle::with(['category', 'author', 'coAuthors', 'tags'])
                ->withCount([
                    'comments as approved_comments_count' => fn ($q) => $q->where('is_approved', true),
                    'comments as pending_comments_count' => fn ($q) => $q->where('is_approved', false),
                    'comments',
                ])
        )->paginate($filters->values['na_strone'])->appends($filters->query());

        return view('blog::admin.index', [
            'articles'   => $articles,
            'filters'    => $filters,
            'status'     => $filters->values['status'],
            'categories' => BlogCategory::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'authors'    => BlogAuthor::orderBy('name')->get(['id', 'name']),
            'tags'       => BlogTag::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /** Akcje zbiorcze na zaznaczonych artykułach (z listy). */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids'         => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'       => ['integer', Rule::exists('blog.blog_articles', 'id')],
            'action'      => ['required', Rule::in(array_keys(self::BULK_ACTIONS))],
            'category_id' => ['nullable', 'required_if:action,category', 'string'],
            'confirm_delete' => ['accepted_if:action,delete'],
        ], [
            'ids.required'               => 'Zaznacz co najmniej jeden artykuł.',
            'action.required'            => 'Wybierz akcję zbiorczą.',
            'category_id.required_if'    => 'Wybierz kategorię do przypisania.',
            'confirm_delete.accepted_if' => 'Potwierdź usunięcie zaznaczonych artykułów.',
        ], ['action' => 'akcja', 'ids' => 'artykuły']);

        $articles = BlogArticle::whereIn('id', $data['ids'])->get();

        switch ($data['action']) {
            case 'publish':
                $articles->each(fn (BlogArticle $a) => $a->update(['is_published' => true, 'published_at' => $a->published_at ?? now()]));
                break;
            case 'draft':
                $articles->each(fn (BlogArticle $a) => $a->update(['is_published' => false]));
                break;
            case 'disable':
                $articles->each(fn (BlogArticle $a) => $a->update(['is_disabled' => true]));
                break;
            case 'enable':
                $articles->each(fn (BlogArticle $a) => $a->update(['is_disabled' => false]));
                break;
            case 'category':
                $categoryId = $data['category_id'] === 'brak' ? null : (int) $data['category_id'];
                if ($categoryId !== null && ! BlogCategory::whereKey($categoryId)->exists()) {
                    throw ValidationException::withMessages(['category_id' => 'Wybrana kategoria nie istnieje.']);
                }
                $articles->each(fn (BlogArticle $a) => $a->update(['category_id' => $categoryId]));
                break;
            case 'delete':
                $articles->each(function (BlogArticle $a) {
                    $this->deleteImageFile($a->image_path, $a->id);
                    $a->delete();
                });
                break;
        }

        $count = $articles->count();
        $back = $request->input('return_to');
        $url = is_string($back) && str_starts_with($back, route('admin.wiem-feer.index')) ? $back : route('admin.wiem-feer.index');

        return redirect()->to($url)->with('status', self::BULK_ACTIONS[$data['action']] . ' — artykułów: ' . $count . '.');
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

        return $this->afterSave($request, $article, 'Artykuł został utworzony.');
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

        return $this->afterSave($request, $article, 'Artykuł został zaktualizowany.');
    }

    /** „Zapisz" zostaje w edycji, „Zapisz i zamknij" (oraz domyślnie) wraca do listy. */
    private function afterSave(Request $request, BlogArticle $article, string $message)
    {
        return $request->input('after_save') === 'edit'
            ? redirect()->route('admin.wiem-feer.edit', $article)->with('status', $message)
            : redirect()->route('admin.wiem-feer.index')->with('status', $message);
    }

    /** Podgląd Markdown w edytorze — ten sam renderer co przy zapisie. */
    public function previewMarkdown(Request $request)
    {
        $data = $request->validate(['markdown' => ['nullable', 'string', 'max:' . self::MARKDOWN_MAX]]);
        $html = MarkdownRenderer::toHtml($data['markdown'] ?? '');

        return response()->json(['html' => $html] + MarkdownRenderer::stats($html));
    }

    /** „Przełącz na Markdown": konwersja bieżącego HTML z edytora wizualnego. */
    public function convertToMarkdown(Request $request)
    {
        $data = $request->validate(['html' => ['nullable', 'string', 'max:' . self::MARKDOWN_MAX]]);

        return response()->json(['markdown' => HtmlToMarkdown::convert($data['html'] ?? '')]);
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
            'body'             => ['nullable', 'string'],
            'body_format'      => ['nullable', Rule::in(BlogArticle::FORMATS)],
            'body_markdown'    => ['nullable', 'string', 'max:' . self::MARKDOWN_MAX],
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
            'body'             => 'treść',
            'body_markdown'    => 'treść (Markdown)',
        ]);

        $this->applyBody($data, $article);

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
     * Treść: w trybie Markdown `body` (HTML) renderujemy z `body_markdown`; w trybie HTML
     * bierzemy `body` z edytora wizualnego (a gdy go nie ma — np. tuż po „Wróć do edytora
     * wizualnego" we wpisie pisanym w Markdown — renderujemy Markdown). Źródło Markdown
     * zostaje zapisane także w trybie HTML, żeby można było do niego wrócić.
     */
    private function applyBody(array &$data, ?BlogArticle $article): void
    {
        $format = $data['body_format'] ?? $article?->body_format ?? BlogArticle::FORMAT_HTML;
        $markdown = isset($data['body_markdown']) ? str_replace(["\r\n", "\r"], "\n", $data['body_markdown']) : null;

        if ($format === BlogArticle::FORMAT_MARKDOWN) {
            if (blank($markdown)) {
                throw ValidationException::withMessages(['body_markdown' => 'Wpisz treść artykułu.']);
            }
            $data['body'] = MarkdownRenderer::toHtml($markdown);
        } else {
            if (blank($data['body'] ?? null) && filled($markdown)) {
                $data['body'] = MarkdownRenderer::toHtml($markdown);
            }
            if (blank($data['body'] ?? null)) {
                throw ValidationException::withMessages(['body' => 'Wpisz treść artykułu.']);
            }
        }

        $data['body_format'] = $format;
        if ($markdown === null) {
            unset($data['body_markdown']);
        }
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
