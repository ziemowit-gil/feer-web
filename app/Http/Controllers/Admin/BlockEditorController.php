<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingSection;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Support\BlockRenderer;
use App\Support\ShortcodeParser;
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
                'more' => 'Adres, kategoria, zdjęcie, zajawka, data i publikacja są w klasycznym formularzu.',
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
    private const LINK_LIMIT = 20;

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
            ->each(fn (Page $p) => $results->push(['title' => $p->title, 'url' => $this->relativeUrl($p->publicUrl()), 'kind' => 'Strona']));

        if ($settings->isModuleEnabled('news')) {
            $base(News::class)->where('is_published', true)->where('published_at', '<=', now())
                ->latest('published_at')->limit($perKind)->get()
                ->each(fn (News $n) => $results->push(['title' => $n->title, 'url' => route('news.show', $n, false), 'kind' => 'Aktualność']));
        }

        if ($settings->isModuleEnabled('projects')) {
            $base(Project::class)->where('is_published', true)->latest('updated_at')->limit($perKind)->get()
                ->each(fn (Project $p) => $results->push(['title' => $p->title, 'url' => route('projects.show', $p, false), 'kind' => 'Działanie']));
        }

        // Przy wyszukiwaniu najpierw tytuły zaczynające się od frazy.
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $results = $results->sortBy(fn ($r) => str_starts_with(mb_strtolower($r['title']), $needle) ? 0 : 1, SORT_REGULAR)->values();
        }

        return response()->json($results->take(self::LINK_LIMIT)->values());
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
        ], [
            'code.regex' => 'Nieprawidłowy shortcode. Wpisz go w nawiasach kwadratowych, np. [formularz:nazwa].',
        ]);

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

        return view('admin.pages.block-editor', [
            'editorConfig' => [
                'target' => ['type' => $type, 'id' => $record->id],
                'labels' => $target['labels'],
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
                'urls' => [
                    'save' => $updateUrl,
                    'view' => $this->publicUrl($type, $record),
                    // ?klasyczny=1 — bez tego domyślny edytor blokowy (Mój profil) odesłałby z formularza z powrotem tutaj.
                    'admin' => $record instanceof ListingSection ? route($target['admin_route']) : route($target['admin_route'], $record).'?klasyczny=1',
                    'images' => route('admin.multimedia.images'),
                    'upload' => route('admin.multimedia.upload-ajax'),
                    'links' => route('admin.edytor-blokowy.links'),
                    'shortcode' => route('admin.edytor-blokowy.shortcode'),
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

        $record->update([
            'title' => $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'blocks' => $blocks,
            'content' => BlockRenderer::render($blocks),
        ]);

        return response()->json([
            'ok' => true,
            'blocks' => $blocks,
            'saved_at' => now()->format('H:i'),
        ]);
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
