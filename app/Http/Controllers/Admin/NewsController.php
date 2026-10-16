<?php

namespace App\Http\Controllers\Admin;

use App\Services\Content\ContentA11yChecker;
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

        $news = News::forCurrentSite()->with(['category', 'tags'])
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

    /**
     * Wyświetla formularz tworzenia nowej aktualności. Z włączonym edytorem blokowym
     * aktualności edytuje się tylko w nim — zakładamy szkic i otwieramy go w edytorze.
     */
    public function create()
    {
        if (self::blockEditorOnly()) {
            $news = News::create([
                'title' => 'Nowa aktualność',
                'slug' => $this->uniqueSlug('nowa-aktualnosc'),
                'published_at' => now(),
                'is_published' => false,
            ]);

            return redirect()->route('admin.edytor-blokowy.edit', ['news', $news->id])
                ->with('status', 'Utworzono szkic aktualności — wpisz tytuł i treść, a w „Ustawieniach aktualności” zdjęcie, kategorię i pozostałe pola.');
        }

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

        return $this->afterSave($request, $news)->with('status', ($request->input('after') === 'draft' ? 'Zapisano wersję roboczą — aktualność nie jest opublikowana.' : 'News został utworzony.') . ContentA11yChecker::saveNotice($news->content));
    }

    /**
     * Dokąd po zapisie — jak w TYPO3: „Zapisz” (after=stay) zostawia redaktora na edytowanym rekordzie,
     * „Zapisz i zamknij” (after=close, a także brak parametru) wraca do listy.
     * „Zapisz wersję roboczą” (after=draft) zapisuje bez publikacji i też zostaje na rekordzie.
     */
    private function afterSave(Request $request, News $news): \Illuminate\Http\RedirectResponse
    {
        // Formularz osadzony w edytorze blokowym wraca do siebie (w ramce okna ustawień).
        if ($request->boolean('osadzony')) {
            return redirect()->route('admin.newsy.edit', [$news, 'osadzony' => 1]);
        }

        return in_array($request->input('after'), ['stay', 'draft'], true)
            ? redirect()->route('admin.newsy.edit', [$news, 'klasyczny' => 1])
            : redirect()->route('admin.newsy.index');
    }

    /** Wyświetla formularz edycji aktualności. */
    public function edit(News $news)
    {
        // Z włączonym edytorem blokowym aktualności edytuje się tylko w nim; formularz
        // (?osadzony=1) służy wtedy za okno „Ustawienia aktualności” wewnątrz edytora.
        if (! request()->boolean('osadzony') && self::blockEditorOnly()) {
            return redirect()->route('admin.edytor-blokowy.edit', ['news', $news->id]);
        }

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
        // Okno „Ustawienia aktualności” w edytorze blokowym nie ma pól, które edytuje sam edytor
        // (tytuł, treść, opis SEO, publikacja) — do walidacji bierzemy zapisane wartości, a przy
        // zapisie ich nie dotykamy (edytor zapisał je chwilę wcześniej).
        $embedded = $request->boolean('osadzony');
        if ($embedded) {
            $request->merge([
                'title' => $news->title,
                'meta_description' => $news->meta_description,
                'published_at' => ($news->published_at ?? now())->format('Y-m-d\TH:i'),
                'is_published' => $news->is_published ? '1' : '0',
                'is_archived' => $news->is_archived ? '1' : '0',
            ]);
        }

        $data = $this->validated($request, $news);
        if ($embedded) {
            unset($data['title'], $data['meta_description'], $data['content'], $data['published_at'],
                $data['is_published'], $data['is_archived'], $data['pending_approval'], $data['submitted_by_id']);
        }
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : ($data['title'] ?? $news->title), $news->id);
        // Treść w blokach: formularz klasyczny jej nie zmienia (edycja tylko w edytorze blokowym).
        if ($news->hasLockedBlockContent()) {
            unset($data['content']);
        }


        $news->update($data);

        $this->handleImage($request, $news);

        $news->tags()->sync($this->resolveTagIds($request->input('tags', '')));

        return $this->afterSave($request, $news)->with('status', ($request->input('after') === 'draft' ? 'Zapisano wersję roboczą — aktualność nie jest opublikowana.' : 'News został zaktualizowany.') . ContentA11yChecker::saveNotice($news->content));
    }

    /** Czy aktualności edytuje się wyłącznie w edytorze blokowym (moduł włączony w Ustawienia → Moduły). */
    public static function blockEditorOnly(): bool
    {
        return SiteSetting::current()->isModuleEnabled('block_editor');
    }

    /**
     * Szybka edycja z paska widocznego na froncie (news/show.blade.php) — tylko
     * tytuł, lead i status publikacji. Celowo pomija slug i treść (WYSIWYG),
     * żeby nie nadpisać ich niepełnymi danymi z front-endu.
     */
    public function quickUpdate(Request $request, News $news)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'is_published' => ['required', 'boolean'],
            // Treść z edytora WYSIWYG na stronie — tylko gdy przesłana (starsze paski jej nie wysyłają).
            'content' => ['sometimes', 'nullable', 'string'],
        ]);

        // Treść w blokach: szybka edycja jej nie zmienia (tylko edytor blokowy).
        if ($news->hasLockedBlockContent()) {
            unset($data['content']);
        }

        $news->update($data);

        return response()->json(['status' => 'ok']);
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
        $clone->is_clone = true;
        $clone->cloned_from_id = $news->id;
        $clone->save();

        // Kopiujemy tagi; zdjęcie (media) trzeba wgrać ponownie — jak przy klonowaniu stron.
        $clone->tags()->sync($news->tags->pluck('id')->all());

        return redirect()->route('admin.newsy.edit', [$clone, 'klasyczny' => 1])
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

    private function validated(Request $request, ?News $news = null): array
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
            'article_layout' => [
                'nullable',
                Rule::in(array_keys(News::ARTICLE_LAYOUTS)),
                // Układ zablokowany w Ustawienia → Moduły — chyba że to bieżący układ edytowanego wpisu.
                Rule::notIn(array_diff(SiteSetting::current()->blocked_options['news_layouts'] ?? [], [$news?->article_layout ?? 'default'])),
            ],
            'content' => ['nullable', 'string'],
            'published_at' => ['required', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
            // Typ wpisu i jego pola
            'post_type' => ['nullable', Rule::in(array_keys(News::POST_TYPES)),
                Rule::notIn(SiteSetting::current()->blockedOptionsExcept('news_post_types', $news?->post_type ?? 'standard'))],
            'expires_at' => ['nullable', 'date', 'after:published_at', 'required_if:post_type,announcement'],
            'unpublish_at' => ['nullable', 'date'],
            'type_data.cta_label' => ['nullable', 'string', 'max:80'],
            'type_data.cta_url' => ['nullable', 'string', 'max:500', 'regex:~^(https?://\S+|/(?!/)\S*|#\S*)$~i'],
            'type_data.important' => ['nullable', 'boolean'],
            'type_data.tool_name' => ['nullable', 'string', 'max:120', 'required_if:post_type,review'],
            'type_data.tool_url' => ['nullable', 'url:http,https', 'max:500'],
            'type_data.tool_version' => ['nullable', 'string', 'max:60'],
            'type_data.score' => ['nullable', 'integer', 'between:1,5', 'required_if:post_type,review'],
            'type_data.compliance' => ['nullable', Rule::in(array_keys(News::REVIEW_COMPLIANCE))],
            'type_data.audit_summary' => ['nullable', 'string', 'max:2000'],
            'type_data.tested_with' => ['nullable', 'string', 'max:300'],
            'type_data.tested_at' => ['nullable', 'date'],
            'type_data.verdict' => ['nullable', 'string', 'max:500'],
            'type_data.pros' => ['nullable', 'array', 'max:20'],
            'type_data.pros.*' => ['nullable', 'string', 'max:300'],
            'type_data.cons' => ['nullable', 'array', 'max:20'],
            'type_data.cons.*' => ['nullable', 'string', 'max:300'],
        ], [
            'expires_at.required_if' => 'Komunikat musi mieć datę ważności — po niej trafi do archiwum.',
            'expires_at.after' => 'Data ważności komunikatu musi być późniejsza niż data publikacji.',
            'type_data.tool_name.required_if' => 'Podaj nazwę testowanego narzędzia.',
            'type_data.score.required_if' => 'Wybierz ocenę dostępności (1–5).',
        ], [
            'expires_at' => 'ważny do',
            'type_data.cta_url' => 'adres przycisku',
            'type_data.tool_url' => 'adres narzędzia',
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
        $data['post_type'] = $data['post_type'] ?? 'standard';
        $td = (array) ($data['type_data'] ?? []);
        // Zapisujemy tylko pola bieżącego typu (bez śmieci po zmianie typu).
        $data['type_data'] = match ($data['post_type']) {
            'announcement' => array_filter([
                'important' => ! empty($td['important']),
                'cta_label' => trim((string) ($td['cta_label'] ?? '')) ?: null,
                'cta_url' => trim((string) ($td['cta_url'] ?? '')) ?: null,
            ], fn ($v) => $v !== null),
            'review' => array_filter([
                'tool_name' => trim((string) ($td['tool_name'] ?? '')),
                'tool_url' => $td['tool_url'] ?? null,
                'tool_version' => $td['tool_version'] ?? null,
                'score' => isset($td['score']) ? (int) $td['score'] : null,
                'compliance' => $td['compliance'] ?? null,
                'audit_summary' => $td['audit_summary'] ?? null,
                'tested_with' => $td['tested_with'] ?? null,
                'tested_at' => $td['tested_at'] ?? null,
                'verdict' => $td['verdict'] ?? null,
                'pros' => array_values(array_filter(array_map('trim', (array) ($td['pros'] ?? [])), 'filled')),
                'cons' => array_values(array_filter(array_map('trim', (array) ($td['cons'] ?? [])), 'filled')),
            ], fn ($v) => $v !== null && $v !== '' && $v !== []),
            default => null,
        };
        $data['expires_at'] = $data['post_type'] === 'announcement' ? ($data['expires_at'] ?? null) : null;
        // „Zapisz wersję roboczą” zapisuje bez publikacji, niezależnie od przełącznika.
        $data['is_published'] = $request->input('after') === 'draft' ? false : $request->boolean('is_published');
        $data['is_archived'] = $request->boolean('is_archived');
        $data['is_clone'] = $request->boolean('is_clone');
        // „Zdejmij z publikacji” (content:unpublish-due); do czasu migracji kolumny nie ma — pomijamy.
        if (\Illuminate\Support\Facades\Schema::hasColumn('news', 'unpublish_at')) {
            $data['unpublish_at'] = $request->filled('unpublish_at') ? $request->input('unpublish_at') : null;
        } else {
            unset($data['unpublish_at']);
        }
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
