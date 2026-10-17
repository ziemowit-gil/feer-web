<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingSection;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Support\BlockRenderer;
use App\Support\ClassicInserts;
use App\Support\NewsList;
use App\Support\PageTypeFields;
use App\Support\ShortcodeParser;
use App\Support\SubpageList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Edytor blokowy (w stylu Gutenberga) — pełnoekranowa aplikacja na stylach
 * frontu, więc redakcja widzi treść tak, jak zobaczy ją odwiedzający.
 *
 * Obsługuje strony, aktualności i działania (mapa TARGETS, jak w
 * InlineEditController). Bloki (JSON) zapisujemy w kolumnie `blocks`, a
 * wyrenderowany z nich HTML w `content` — reszta serwisu (szablony,
 * wyszukiwarka, spis treści, shortcode'y, historia wersji) korzysta
 * z `content` jak dotąd.
 *
 * Dostęp do modułu sprawdzamy w kontrolerze (trasy ogólne `{type}/{id}` nie
 * siedzą w grupie z middleware konkretnego modułu).
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlockEditorController extends Controller
{
    use \App\Http\Controllers\Concerns\HandlesContentApproval;

    /** Obsługiwane typy treści: model, moduł uprawnień, trasa formularza i etykiety interfejsu. */
    private const TARGETS = [
        'page' => [
            'model' => Page::class,
            'module' => 'pages',
            'admin_route' => 'admin.podstrony.edit',
            'labels' => [
                'kind' => 'Strona',
                'title' => 'Tytuł strony',
                'back' => 'Wróć do strony',
                'view' => 'Zobacz stronę',
                'form' => 'Otwórz formularz strony',
                'typeLabel' => 'Typ strony',
                'published' => 'opublikowana',
                'publishedLong' => 'Opublikowana',
                'draftLong' => 'Szkic (niewidoczna publicznie)',
                'contentLabel' => 'Treść strony',
                'more' => 'Adres, menu, publikacja, dostęp, szablon i pola typu strony są w klasycznym formularzu.',
                'imported' => 'Ta strona była edytowana w klasycznym edytorze',
                'empty' => 'Strona nie ma jeszcze bloków.',
                'titleRequired' => 'Tytuł strony nie może być pusty.',
                'noscript' => 'Przejdź do klasycznego formularza strony',
            ],
        ],
        'news' => [
            'model' => News::class,
            'module' => 'news',
            'admin_route' => 'admin.newsy.edit',
            'labels' => [
                'kind' => 'Aktualność',
                'title' => 'Tytuł aktualności',
                'back' => 'Wróć do aktualności',
                'view' => 'Zobacz aktualność',
                'form' => 'Otwórz formularz aktualności',
                'typeLabel' => 'Rodzaj',
                'published' => 'opublikowana',
                'publishedLong' => 'Opublikowana',
                'draftLong' => 'Szkic (niewidoczna publicznie)',
                'contentLabel' => 'Treść aktualności',
                'more' => 'Zdjęcie, adres, kategoria, zajawka, tagi, typ wpisu, układ, SEO, ETR, PJM i pliki są w oknie ustawień aktualności.',
                'imported' => 'Ta aktualność była edytowana w klasycznym edytorze',
                'empty' => 'Aktualność nie ma jeszcze bloków.',
                'titleRequired' => 'Tytuł aktualności nie może być pusty.',
                'noscript' => 'Przejdź do klasycznego formularza aktualności',
            ],
        ],
        'project' => [
            'model' => Project::class,
            'module' => 'projects',
            'admin_route' => 'admin.projekty.edit',
            'labels' => [
                'kind' => 'Działanie',
                'title' => 'Tytuł działania',
                'back' => 'Wróć do działania',
                'view' => 'Zobacz działanie',
                'form' => 'Otwórz formularz działania',
                'typeLabel' => 'Rodzaj',
                'published' => 'opublikowane',
                'publishedLong' => 'Opublikowane',
                'draftLong' => 'Szkic (niewidoczne publicznie)',
                'contentLabel' => 'Opis działania',
                'more' => 'Adres, kategoria, zdjęcie, „dla kogo”, efekty, FAQ, koordynator i publikacja są w klasycznym formularzu. Edytor blokowy zmienia tylko opis działania.',
                'imported' => 'To działanie było edytowane w klasycznym edytorze',
                'empty' => 'Działanie nie ma jeszcze bloków.',
                'titleRequired' => 'Tytuł działania nie może być pusty.',
                'noscript' => 'Przejdź do klasycznego formularza działania',
            ],
        ],
        'listing' => [
            'model' => ListingSection::class,
            'module' => 'projects',
            'admin_route' => 'admin.projekty.index',
            'labels' => [
                'kind' => 'Bloki na stronie listy',
                'title' => 'Nazwa miejsca (tylko w panelu)',
                'back' => 'Wróć do listy działań',
                'view' => 'Zobacz stronę Działania',
                'form' => 'Otwórz listę działań w panelu',
                'typeLabel' => 'Miejsce',
                'published' => 'widoczne',
                'publishedLong' => 'Widoczne na stronie',
                'draftLong' => 'Ukryte',
                'contentLabel' => 'Bloki pod menu kategorii',
                'more' => 'Bloki pokazują się na stronie „Działania” w wąskiej lewej kolumnie, pod menu kategorii — najlepiej sprawdzą się krótkie: tekst, przycisk, zdjęcie, kontakt. Usuń wszystkie bloki, aby nic się tam nie pokazywało.',
                'imported' => 'To miejsce miało wcześniej treść HTML',
                'empty' => 'Pod menu kategorii nie ma jeszcze bloków.',
                'titleRequired' => 'Nazwa miejsca nie może być pusta.',
                'noscript' => 'Przejdź do listy działań w panelu',
            ],
        ],
    ];

    /** Maksymalna liczba podpowiedzi w wyborze odnośnika wewnętrznego. */
    private const LINK_LIMIT = 30;

    // ── Trasy dotychczasowe (podstrony) ─────────────────────────────────────

    public function editPage(Page $page)
    {
        return $this->editRecord('page', $page);
    }

    public function updatePage(Request $request, Page $page): JsonResponse
    {
        return $this->updateRecord($request, 'page', $page);
    }

    // ── Trasy ogólne: admin/edytor-blokowy/{type}/{id} ──────────────────────

    /** Wejście z panelu/strony: tworzy (przy pierwszym razie) miejsce na bloki i otwiera edytor. */
    public function editListing(string $key)
    {
        abort_unless(isset(ListingSection::PLACES[$key]), 404);
        $module = ListingSection::PLACES[$key]['module'];
        abort_unless(auth()->user()->canAccessModule($module), 403);
        abort_unless(SiteSetting::current()->isModuleEnabled($module), 404);

        return redirect()->route('admin.edytor-blokowy.edit', ['listing', ListingSection::place($key)->id]);
    }

    public function edit(string $type, int $id)
    {
        return $this->editRecord($type, $this->findRecord($type, $id));
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        return $this->updateRecord($request, $type, $this->findRecord($type, $id));
    }

    /**
     * Wyszukiwarka odnośników wewnętrznych (opublikowane strony, aktualności
     * i działania) dla okna „Wstaw odnośnik” i adresów przycisków.
     * Zwraca adresy względne (sama ścieżka), więc działają też po zmianie domeny.
     */
    public function links(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(collect(self::TARGETS)->contains(fn ($t) => $user->canAccessModule($t['module'])), 403);

        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $settings = SiteSetting::current();
        $perKind = $q === '' ? 7 : self::LINK_LIMIT;

        // Linkować można do każdej opublikowanej treści — zakres edytora (własne wpisy) tu nie obowiązuje.
        $base = function (string $class) use ($q) {
            $query = $class::query()->withoutGlobalScope('editor_scope')->forCurrentSite();
            if ($q !== '') {
                $query->where(fn ($w) => $w->where('title', 'like', '%'.$q.'%')->orWhere('slug', 'like', '%'.$q.'%'));
            }

            return $query;
        };

        $results = collect();

        $base(Page::class)->live()->latest('updated_at')->limit($perKind)->get()
            ->each(fn (Page $p) => $results->push(['title' => $p->title, 'url' => $this->relativeUrl($p->publicUrl()), 'kind' => $p->isAboutPerson() ? 'Osoba' : 'Strona']));

        if ($settings->isModuleEnabled('news')) {
            $base(News::class)->where('is_published', true)->where('published_at', '<=', now())
                ->latest('published_at')->limit($perKind)->get()
                ->each(fn (News $n) => $results->push(['title' => $n->title, 'url' => route('news.show', $n, false), 'kind' => 'Aktualność']));
        }

        if ($settings->isModuleEnabled('projects')) {
            $base(Project::class)->where('is_published', true)->latest('updated_at')->limit($perKind)->get()
                ->each(fn (Project $p) => $results->push(['title' => $p->title, 'url' => route('projects.show', $p, false), 'kind' => 'Działanie']));
        }

        // Jak w menu „Wstaw link" klasycznego edytora: także wydarzenia i pliki do pobrania.
        if ($settings->isModuleEnabled('events')) {
            $base(\App\Models\Event::class)->where('is_published', true)->latest('starts_at')->limit($perKind)->get()
                ->each(fn (\App\Models\Event $e) => $results->push(['title' => $e->title, 'url' => route('events.show', $e, false), 'kind' => 'Wydarzenie']));
        }

        \App\Models\Attachment::query()->with('media')
            ->when($q !== '', fn ($w) => $w->where('label', 'like', '%'.$q.'%'))
            ->latest('id')->limit($perKind)->get()
            ->filter(fn ($a) => $a->file_url)
            ->each(fn ($a) => $results->push(['title' => $a->label, 'url' => $a->file_url, 'kind' => 'Plik'.($a->file_extension ? ' '.strtoupper($a->file_extension) : '')]));

        // Przy wyszukiwaniu najpierw tytuły zaczynające się od frazy.
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $results = $results->sortBy(fn ($r) => str_starts_with(mb_strtolower($r['title']), $needle) ? 0 : 1, SORT_REGULAR)->values();
        }

        return response()->json($results->take(self::LINK_LIMIT)->values());
    }

    /**
     * Publikuje siatkę kafelków (strona typu tiles_grid) wskazaną w bloku „Shortcode” — przycisk „Opublikuj”
     * przy komunikacie „Siatka jest szkicem”. Tylko dla osób z dostępem do stron i tylko dla siatek.
     */
    public function publishTilesGrid(Request $request, Page $page): JsonResponse
    {
        abort_unless($request->user()->canAccessModule('pages'), 403);
        abort_unless($page->type === 'tiles_grid', 404);

        $page->update(['is_published' => true]);

        return response()->json(['ok' => true, 'message' => 'Opublikowano siatkę „'.$page->title.'”.']);
    }

    /**
     * Podgląd shortcode'u (blok „Shortcode”) albo HTML-a z shortcode'ami (blok
     * „Własny HTML”) — ten sam renderer co na stronie publicznej. Edytor wstawia
     * wynik w nieaktywny kontener podglądu, bez skryptów.
     */
    public function shortcodePreview(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(collect(self::TARGETS)->contains(fn ($t) => $user->canAccessModule($t['module'])), 403);

        $data = $this->validateJson($request, [
            'code' => ['required_without:html', 'nullable', 'string', 'max:220', 'regex:/^\[[a-z0-9_-]+(?::[^\]\r\n]{1,200})?\]$/i'],
            'html' => ['required_without:code', 'nullable', 'string', 'max:200000'],
            // Strona, dla której liczyć „bieżącą stronę" listy podstron ([podstrony]).
            'page_id' => ['nullable', 'integer'],
        ], [
            'code.regex' => 'Nieprawidłowy shortcode. Wpisz go w nawiasach kwadratowych, np. [formularz:nazwa].',
        ]);

        if (filled($data['page_id'] ?? null)) {
            SubpageList::setContextPage(Page::query()->forCurrentSite()->find($data['page_id']));
        }

        $html = filled($data['code'] ?? null)
            ? ShortcodeParser::render('<p>'.$data['code'].'</p>')
            : ShortcodeParser::render((string) $data['html']);

        // Podgląd nigdy nie wykonuje skryptów (dodatkowo czyści je edytor).
        $html = preg_replace('~<script\b[^>]*>.*?</script\s*>~is', '', $html) ?? '';

        return response()->json(['html' => $html]);
    }

    // ── Wspólna logika ──────────────────────────────────────────────────────

    private function target(string $type): array
    {
        abort_unless(isset(self::TARGETS[$type]), 404);

        return self::TARGETS[$type];
    }

    /** Rekord z uwzględnieniem zakresu edytora (globalny scope na trasach admin.*) i bieżącej witryny. */
    private function findRecord(string $type, int $id): Model
    {
        $target = $this->target($type);
        abort_unless(auth()->user()->canAccessModule($target['module']), 403);
        abort_unless(SiteSetting::current()->isModuleEnabled($target['module']), 404);

        return $target['model']::query()->forCurrentSite()->findOrFail($id);
    }

    private function authorizeRecord(string $type, Model $record): array
    {
        $target = $this->target($type);
        abort_unless(auth()->user()->canAccessModule($target['module']), 403);

        if ($record instanceof Page) {
            abort_if($record->is_locked && ! auth()->user()->isAdmin(), 403, 'Strona została zablokowana do edycji przez administratora.');
        }

        return $target;
    }

    private function editRecord(string $type, Model $record)
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('block_editor'), 404); // wyłączony w Ustawienia → Moduły
        $target = $this->authorizeRecord($type, $record);

        // Treść bez bloków (np. edytowana dotąd klasycznie) startuje z jednym blokiem HTML.
        $blocks = $record->blocks;
        $imported = false;
        if (! is_array($blocks)) {
            $blocks = filled($record->content) ? [['type' => 'html', 'attrs' => ['html' => (string) $record->content]]] : [];
            $imported = filled($record->content);
        }

        $updateUrl = $type === 'page'
            ? route('admin.podstrony.bloki.update', $record)
            : route('admin.edytor-blokowy.update', [$type, $record->id]);

        // Pola typu strony (wydarzenie, harmonogram, nabór…) — okno „Pola typu strony”, zapis razem z blokami.
        $typeFieldsHtml = $record instanceof Page ? PageTypeFields::render($record) : null;

        return view('admin.pages.block-editor', [
            'typeFieldsHtml' => $typeFieldsHtml,
            'editorConfig' => [
                'target' => ['type' => $type, 'id' => $record->id],
                'labels' => $typeFieldsHtml !== null
                    ? ['more' => 'Adres, menu, publikacja, dostęp i szablon są w klasycznym formularzu.'] + $target['labels']
                    : $target['labels'],
                'page' => [
                    'id' => $record->id,
                    'title' => $record->title,
                    'meta_description' => (string) $record->meta_description,
                    'type' => $this->typeLabel($type, $record),
                    'is_published' => (bool) $record->is_published,
                ],
                'blocks' => BlockRenderer::normalize($blocks),
                // Działanie: płótno w układzie strony działania (kategoria, „Opis działania”, podgląd menu bocznego).
                'layout' => $record instanceof Project ? $this->projectLayout($record) : null,
                'imported' => $imported,
                'typeFields' => $typeFieldsHtml !== null ? ['name' => PageTypeFields::typeName($record)] : null,
                // Blok „Lista podstron": strony do wyboru źródła i wyboru ręcznego.
                'subpages' => [
                    'pages' => SubpageList::pickerOptions(),
                    'contextPage' => $record instanceof Page ? $record->id : null,
                ],
                // Kafelki strony (pole „Kafelki na stronie" i nawigacja kafelkowa podstron) — podgląd nad/pod treścią, jak na stronie.
                'pageTiles' => $record instanceof Page ? $this->pageTiles($record) : null,
                // Publikacja, wersja robocza, kosz i miejsce w serwisie — jak w panelu bocznym formularza.
                'settings' => $this->settingsConfig($type, $record),
                // Elementy z menu „Wstaw" klasycznego edytora (moduły, wstawki HTML, wydarzenia).
                'classic' => ClassicInserts::forBlockEditor(),
                // Blok „Lista aktualności": kategorie do wyboru.
                'newsCategories' => NewsList::categoryOptions(),
                'urls' => [
                    'save' => $updateUrl,
                    'view' => $this->publicUrl($type, $record),
                    // ?klasyczny=1 — bez tego domyślny edytor blokowy (Mój profil) odesłałby z formularza z powrotem tutaj.
                    // Aktualności (z włączonym edytorem) mają formularz tylko jako okno „Ustawienia aktualności”.
                    'admin' => match (true) {
                        $record instanceof ListingSection => route($target['admin_route']),
                        $record instanceof News => route('admin.newsy.edit', [$record, 'osadzony' => 1]),
                        default => route($target['admin_route'], $record).'?klasyczny=1',
                    },
                    'newsForm' => $record instanceof News ? route('admin.newsy.edit', [$record, 'osadzony' => 1]) : null,
                    'images' => route('admin.multimedia.images'),
                    'upload' => route('admin.multimedia.upload-ajax'),
                    'links' => route('admin.edytor-blokowy.links'),
                    'shortcode' => route('admin.edytor-blokowy.shortcode'),
                    // Szablony treści („Dodaj z szablonu / Zarządzaj szablonami" jak w formularzu) — strony i aktualności.
                    'templates' => in_array($type, ['page', 'news'], true) ? [
                        'type' => $type,
                        'base' => url('admin/szablony'),
                        'store' => \App\Support\Edition::allows('content_templates') ? route('admin.szablony.store') : null,
                    ] : null,
                    // Historia wersji treści (jak przycisk „Historia wersji" klasycznego edytora).
                    'history' => in_array($type, ['page', 'news', 'project'], true) ? route('admin.historia.json', ['type' => $type, 'id' => $record->id]) : null,
                    // Podgląd wersji roboczej — podpisany link (strona publiczna dla szkicu zwraca 404).
                    'preview' => method_exists($record, 'previewUrl') ? $record->previewUrl() : null,
                    // Import treści z Worda (jak „Import → DOCX" klasycznego edytora) — tylko w wersji z tą funkcją.
                    'docx' => \App\Support\Edition::allows('docx_import') ? route('admin.editor.docx.import') : null,
                ],
            ],
        ]);
    }

    private function updateRecord(Request $request, string $type, Model $record): JsonResponse
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('block_editor'), 404); // wyłączony w Ustawienia → Moduły
        $this->authorizeRecord($type, $record);

        $data = $this->validateJson($request, [
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'blocks' => ['present', 'array', 'max:'.BlockRenderer::MAX_BLOCKS],
        ]);

        $blocks = BlockRenderer::normalize($data['blocks']);
        $typeColumns = $this->typeColumns($request, $record);
        $settings = $this->settingsColumns($request, $record);

        $record->update($settings + $typeColumns + [
            'title' => $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'blocks' => $blocks,
            'content' => BlockRenderer::render($blocks),
        ]);
        $this->applyNewsImage($request, $record);

        return response()->json([
            'ok' => true,
            'blocks' => $blocks,
            'saved_at' => now()->format('H:i'),
            'settings' => $this->settingsValues($record->fresh()),
        ]);
    }

    /**
     * Najważniejsze ustawienia z panelu bocznego formularza (widoczne tam bez przewijania):
     * publikacja, termin publikacji, treść archiwalna, wersja robocza, a dla stron — miejsce
     * w serwisie (strona nadrzędna, działanie, sposób pokazania, kolejność). Wysyłane tylko
     * po zmianie (klucz `settings`); publikacja przechodzi przez zatwierdzanie jak w formularzu.
     *
     * @return array<string, mixed>
     */
    private function settingsColumns(Request $request, Model $record): array
    {
        if (! $request->has('settings') || $record instanceof ListingSection) {
            return [];
        }

        $rules = ['is_published' => ['required', 'boolean'], 'draft' => ['nullable', 'boolean']];
        if ($record instanceof Page) {
            $rules += [
                'publish_at' => ['nullable', 'date'],
                'is_archived' => ['nullable', 'boolean'],
                'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
                'project_id' => ['nullable', 'integer', 'exists:projects,id'],
                'project_display' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Page::PROJECT_DISPLAYS))],
                'order' => ['nullable', 'integer', 'min:0'],
                'show_in_menu' => ['nullable', 'boolean'],
                'hide_in_sitemap' => ['nullable', 'boolean'],
                'show_side_nav' => ['nullable', 'boolean'],
                'side_nav_style' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Page::SIDE_NAV_STYLES))],
            ];
        } elseif ($record instanceof News) {
            $rules += [
                'published_at' => ['nullable', 'date'],
                'is_archived' => ['nullable', 'boolean'],
                'excerpt' => ['nullable', 'string', 'max:255'],
                'news_category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
                'project_id' => ['nullable', 'integer', 'exists:projects,id'],
                'audience' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(SiteSetting::current()->audienceOptions()))],
                'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'image_alt' => ['nullable', 'string', 'max:255'],
                'library_media_id' => ['nullable', 'integer'],
                'delete_image' => ['nullable', 'boolean'],
                // Typ wpisu i komunikat z przyciskiem CTA (jak w formularzu aktualności).
                'post_type' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(News::POST_TYPES)),
                    \Illuminate\Validation\Rule::notIn(SiteSetting::current()->blockedOptionsExcept('news_post_types', $record->post_type ?? 'standard'))],
                'expires_at' => ['nullable', 'date', 'required_if:post_type,announcement'],
                'important' => ['nullable', 'boolean'],
                'cta_label' => ['nullable', 'string', 'max:80'],
                'cta_url' => ['nullable', 'string', 'max:500', 'regex:~^(https?://\S+|/(?!/)\S*|#\S*)$~i'],
            ];
        }
        if ($record instanceof Page || $record instanceof News) {
            $rules += ['slug' => ['nullable', 'string', 'max:255'], 'meta_title' => ['nullable', 'string', 'max:255'], 'unpublish_at' => ['nullable', 'date']];
        }

        $input = (array) $request->input('settings', []);
        $validator = validator($input, $rules, [
            'expires_at.required_if' => 'Komunikat musi mieć datę ważności — po niej trafi do archiwum.',
            'cta_url.regex' => 'Adres przycisku: https://…, /strona albo #kotwica.',
            'parent_id.exists' => 'Wybrana strona nadrzędna nie istnieje.',
            'project_id.exists' => 'Wybrane działanie nie istnieje.',
        ]);
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()->toArray()], 422));
        }
        $v = $validator->validated();

        $data = ['is_published' => ! ($v['draft'] ?? false) && (bool) $v['is_published']];
        if ($record instanceof Page) {
            $parentId = ($v['parent_id'] ?? null) ?: null;
            if ($parentId) {
                $parent = Page::find($parentId);
                if ($parent->id === $record->id || $parent->ancestors()->contains('id', $record->id)) {
                    throw new HttpResponseException(response()->json(['message' => 'Strona nie może być podstroną samej siebie ani swojej podstrony.'], 422));
                }
            }
            $projectId = ($v['project_id'] ?? null) ?: null;
            $data += [
                'publish_at' => $v['publish_at'] ?? null,
                'is_archived' => (bool) ($v['is_archived'] ?? false),
                'parent_id' => $parentId,
                'project_id' => $projectId,
                'project_display' => $projectId ? ($v['project_display'] ?? 'link') : 'link',
                'order' => (int) ($v['order'] ?? $record->order ?? 0),
                'show_in_menu' => (bool) ($v['show_in_menu'] ?? $record->show_in_menu),
                'hide_in_sitemap' => (bool) ($v['hide_in_sitemap'] ?? $record->hide_in_sitemap),
                'show_side_nav' => (bool) ($v['show_side_nav'] ?? $record->show_side_nav),
            ];
            // Styl ukryty w Ustawienia → Moduły nie przejdzie — zostaje bieżący (jak w formularzu).
            $style = $v['side_nav_style'] ?? $record->side_nav_style ?? 'sidebar';
            $data['side_nav_style'] = in_array($style, SiteSetting::current()->blockedOptionsExcept('side_nav_styles', $record->side_nav_style), true)
                ? ($record->side_nav_style ?: 'sidebar') : $style;
            // Adres: strony osób i systemowe mają adres nadawany automatycznie — bez zmian.
            if (array_key_exists('slug', $v) && ! $record->isAboutPerson() && ! $record->is_system) {
                $data['slug'] = \App\Support\UniqueSlug::page(trim((string) $v['slug']) !== '' ? (string) $v['slug'] : (string) $request->input('title'), $record->id);
            }
        } elseif ($record instanceof News) {
            $data += [
                'published_at' => $v['published_at'] ?? $record->published_at ?? now(),
                'is_archived' => (bool) ($v['is_archived'] ?? false),
                'excerpt' => $v['excerpt'] ?? null,
                'news_category_id' => ($v['news_category_id'] ?? null) ?: null,
                'project_id' => ($v['project_id'] ?? null) ?: null,
                'audience' => $v['audience'] ?? ($record->audience ?: 'brand'),
                'accent_color' => filled($v['accent_color'] ?? null) ? SiteSetting::current()->contrastSafeColor($v['accent_color']) : null,
                'image_alt' => $v['image_alt'] ?? null,
            ];
            if (array_key_exists('post_type', $v)) {
                $postType = $v['post_type'] ?: 'standard';
                $td = is_array($record->type_data) ? $record->type_data : [];
                if ($postType === 'announcement') {
                    $td = array_merge($td, ['important' => (bool) ($v['important'] ?? false), 'cta_label' => $v['cta_label'] ?? null, 'cta_url' => $v['cta_url'] ?? null]);
                }
                $data += ['post_type' => $postType, 'type_data' => $td, 'expires_at' => $postType === 'announcement' ? ($v['expires_at'] ?? null) : null];
            }
            if (array_key_exists('slug', $v)) {
                $data['slug'] = \App\Support\UniqueSlug::news(trim((string) $v['slug']) !== '' ? (string) $v['slug'] : (string) $request->input('title'), $record->id);
            }
        }
        if ($record instanceof Page || $record instanceof News) {
            if (array_key_exists('meta_title', $v)) {
                $data['meta_title'] = $v['meta_title'] ?: null;
            }
            // „Zdejmij z publikacji" — kolumna dochodzi z migracją.
            if (array_key_exists('unpublish_at', $v) && \Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'unpublish_at')) {
                $data['unpublish_at'] = $v['unpublish_at'] ?: null;
            }
        }

        return in_array(\App\Models\Concerns\Approvable::class, class_uses_recursive($record), true)
            ? $this->applyApprovalWorkflow($data)
            : $data;
    }

    /** Zdjęcie aktualności z biblioteki multimediów albo jego usunięcie (jak w formularzu). */
    private function applyNewsImage(Request $request, Model $record): void
    {
        if (! $record instanceof News || ! $request->has('settings')) {
            return;
        }
        $s = (array) $request->input('settings', []);
        if (! empty($s['delete_image'])) {
            $record->clearMediaCollection('image');
            $record->update(['image_alt' => null]);
        } elseif (! empty($s['library_media_id']) && ($source = \Spatie\MediaLibrary\MediaCollections\Models\Media::find((int) $s['library_media_id']))) {
            $source->copy($record, 'image');
            $record->refreshImageDimensions();
        }
    }

    /** Bieżące wartości ustawień panelu bocznego (do edytora). @return array<string, mixed>|null */
    private function settingsValues(Model $record): ?array
    {
        if ($record instanceof ListingSection) {
            return null;
        }
        $base = ['is_published' => (bool) $record->is_published, 'pending_approval' => (bool) ($record->pending_approval ?? false)];

        return match (true) {
            $record instanceof Page => $base + [
                'publish_at' => $record->publish_at?->format('Y-m-d\TH:i'),
                'is_archived' => (bool) $record->is_archived,
                'parent_id' => $record->parent_id,
                'project_id' => $record->project_id,
                'project_display' => $record->project_display ?: 'link',
                'order' => (int) $record->order,
                'show_in_menu' => (bool) $record->show_in_menu,
                'hide_in_sitemap' => (bool) $record->hide_in_sitemap,
                'show_side_nav' => (bool) ($record->show_side_nav ?? true),
                'side_nav_style' => $record->side_nav_style ?: 'sidebar',
                'slug' => (string) $record->slug,
                'slug_editable' => ! $record->isAboutPerson() && ! $record->is_system,
                'meta_title' => (string) $record->meta_title,
                'unpublish_at' => $record->unpublish_at?->format('Y-m-d\TH:i'),
            ],
            $record instanceof News => $base + [
                'published_at' => $record->published_at?->format('Y-m-d\TH:i'),
                'is_archived' => (bool) $record->is_archived,
                'excerpt' => (string) $record->excerpt,
                'news_category_id' => $record->news_category_id,
                'project_id' => $record->project_id,
                'audience' => $record->audience ?: 'brand',
                'accent_color' => (string) $record->accent_color,
                'image_url' => $record->image_url,
                'post_type' => $record->post_type ?: 'standard',
                'expires_at' => $record->expires_at?->format('Y-m-d\TH:i'),
                'important' => (bool) ($record->type_data['important'] ?? false),
                'cta_label' => (string) ($record->type_data['cta_label'] ?? ''),
                'cta_url' => (string) ($record->type_data['cta_url'] ?? ''),
                'image_alt' => (string) $record->image_alt,
                'slug' => (string) $record->slug,
                'slug_editable' => true,
                'meta_title' => (string) $record->meta_title,
                'unpublish_at' => $record->unpublish_at?->format('Y-m-d\TH:i'),
            ],
            default => $base,
        };
    }

    /** Listy wyboru i adresy akcji do ustawień panelu bocznego. @return array<string, mixed>|null */
    private function settingsConfig(string $type, Model $record): ?array
    {
        $values = $this->settingsValues($record);
        if ($values === null) {
            return null;
        }
        $destroy = ['page' => 'admin.podstrony.destroy', 'news' => 'admin.newsy.destroy', 'project' => 'admin.projekty.destroy'][$type] ?? null;
        $index = ['page' => 'admin.podstrony.index', 'news' => 'admin.newsy.index', 'project' => 'admin.projekty.index'][$type] ?? null;
        $locked = $record instanceof Page && ($record->is_system || $record->is_locked);

        return [
            'values' => $values,
            'destroy' => $destroy && ! $locked ? route($destroy, $record) : null,
            'index' => $index ? route($index) : null,
            'parents' => $record instanceof Page
                ? Page::where('id', '!=', $record->id)->orderBy('title')->get(['id', 'title'])->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->all()
                : [],
            'projects' => $record instanceof Page || $record instanceof News
                ? Project::orderBy('title')->get(['id', 'title'])->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->all()
                : [],
            'projectDisplays' => Page::PROJECT_DISPLAYS,
            'sideNavStyles' => collect(Page::SIDE_NAV_STYLES)->except(SiteSetting::current()->blockedOptionsExcept('side_nav_styles', $record->side_nav_style ?? null))->all(),
            'audiences' => SiteSetting::current()->audienceOptions(),
            'categories' => $record instanceof News ? NewsList::categoryOptions() : [],
            'postTypes' => $record instanceof News
                ? collect(SiteSetting::current()->availableOptions('news_post_types', News::POST_TYPES, $record->post_type))->map(fn ($o) => $o['label'])->all()
                : [],
            'wasPublished' => (bool) $record->is_published,
            'publicBase' => $record instanceof News ? url('/aktualnosci').'/' : url('/'),
            'canApprove' => (bool) auth()->user()?->canApproveContent(),
        ];
    }

    /**
     * Pola typu strony z okna „Pola typu strony” (klucz `fields`, nazwy jak w formularzu):
     * walidacja regułami typu i normalizacja jak w formularzu; zapisywane są tylko kolumny
     * bieżącego typu. Bez klucza `fields` (np. strona bez pól typu) nic się nie zmienia.
     *
     * @return array<string, mixed>
     */
    private function typeColumns(Request $request, Model $record): array
    {
        if (! $record instanceof Page || ! $request->has('fields') || ! PageTypeFields::supports($record)) {
            return [];
        }

        $input = (array) $request->input('fields', []);
        $validator = validator($input, PageTypeFields::rules($record->type));
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Pola typu strony: '.$validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        return PageTypeFields::columns($record->type, $validator->validated(), $input);
    }

    /**
     * Walidacja z odpowiedzią JSON 422 — aplikacja renderuje JSON-owe błędy tylko
     * dla api/*, a edytor (fetch) potrzebuje komunikatu, nie przekierowania.
     */
    private function validateJson(Request $request, array $rules, array $messages = []): array
    {
        $validator = validator($request->all(), $rules, $messages);
        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        return $validator->validated();
    }

    /** @return array{kind: string, kicker: string, heading: string, navTitle: string, nav: array<int, string>} */
    private function projectLayout(Project $project): array
    {
        $sections = $project->sections_as_tabs
            ? collect($project->custom_sections ?? [])->filter(fn ($s) => ! empty($s['title']))->pluck('title')
            : collect();
        $tabPages = $project->pageTree(true)->where('project_display', 'tab')->pluck('title');

        return [
            'kind' => 'project',
            'kicker' => (string) ($project->category?->name ?? ''),
            'heading' => 'Opis działania',
            'navTitle' => (string) ($project->nav_title ?: $project->title),
            'nav' => collect(['O projekcie'])->merge($sections)->merge($tabPages)->values()->all(),
        ];
    }

    /**
     * Kafelki, które strona pokazuje poza treścią: „Kafelki na stronie" (nad albo pod treścią)
     * oraz nawigacja kafelkowa podstron. HTML z tych samych widoków co na stronie publicznej.
     *
     * @return array{before: ?string, after: ?string}
     */
    private function pageTiles(Page $page): array
    {
        $out = ['before' => null, 'after' => null];

        if (! $page->isTilesGrid() && $page->showsTiles()) {
            $tiles = collect($page->tiles ?? [])
                ->filter(fn ($t) => filled($t['heading'] ?? null) || (filled($t['label'] ?? null) && filled($t['url'] ?? null)))
                ->values();
            if ($tiles->isNotEmpty()) {
                $out[$page->tiles_content_position === 'below' ? 'before' : 'after'] = view('partials._tiles-sections', ['tiles' => $tiles->all(), 'label' => $page->title])->render();
            }
        }

        if ($page->sideNavStyle() === 'tiles' && $page->publishedChildren->isNotEmpty()) {
            $out['after'] = ($out['after'] ?? '').view('partials.page-tiles-nav', [
                'tiles' => $page->publishedChildren()->orderBy('order')->orderBy('title')->get(),
                'page' => $page,
            ])->render();
        }

        return $out;
    }

    private function typeLabel(string $type, Model $record): string
    {
        return match (true) {
            $record instanceof Page => Page::types()[$record->type] ?? (string) $record->type,
            $record instanceof News => $record->category?->name ? 'Aktualność · '.$record->category->name : 'Aktualność',
            $record instanceof Project => $record->category?->name ? 'Działanie · '.$record->category->name : 'Działanie',
            $record instanceof ListingSection => 'Działania · pod menu kategorii',
            default => self::TARGETS[$type]['labels']['kind'],
        };
    }

    private function publicUrl(string $type, Model $record): string
    {
        return match (true) {
            $record instanceof Page => $record->publicUrl(),
            $record instanceof News => route('news.show', $record),
            $record instanceof Project => route('projects.show', $record),
            $record instanceof ListingSection => $record->publicUrl(),
        };
    }

    /** Sama ścieżka (z zapytaniem) adresu — odnośniki wewnętrzne zapisujemy względnie. */
    private function relativeUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        return ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
