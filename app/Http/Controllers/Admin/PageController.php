<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesContentApproval;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Panel admin: zarządzanie podstronami z rozbudowanym formularzem (wiele typów treści,
 * harmonogram, sekcje „O organizacji", kontrola dostępu) i operacjami zbiorczymi.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), toggleVisibility(),
 *         toggleFeatured(), toggleDisabled(), updateOrder(), bulk(), clone().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PageController extends Controller
{
    use HandlesContentApproval;

    /** Wyświetla listę podstron z wyszukiwaniem i filtrowaniem statusu. */
    public function index(Request $request)
    {
        $search = $request->query('q', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'default');

        $pages = Page::with('parent')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($sort === 'title_asc', fn ($q) => $q->orderBy('title'))
            ->when($sort === 'title_desc', fn ($q) => $q->orderByDesc('title'))
            ->when($sort === 'default', fn ($q) => $q->orderByDesc('is_featured')->orderBy('order')->orderBy('title'))
            ->paginate(30)
            ->withQueryString();

        return view('admin.pages.index', [
            'pages'  => $pages,
            'q'      => $search,
            'status' => $status,
            'sort'   => $sort,
        ]);
    }

    /** Wyświetla formularz tworzenia nowej podstrony. */
    public function create()
    {
        return view('admin.pages.form', [
            'page' => new Page,
            'parentOptions' => Page::orderBy('title')->get(),
            'projectOptions' => Project::orderBy('title')->get(),
            'partnerOptions' => \App\Models\Partner::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    /** Zapisuje nową podstronę z unikalnym slugiem. */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        Page::create($data);

        return redirect()->route('admin.podstrony.index')->with('status', 'Strona została utworzona.');
    }

    /** Wyświetla formularz edycji podstrony (zablokowaną stronę może edytować tylko admin). */
    public function edit(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        return view('admin.pages.form', [
            'page' => $page,
            'parentOptions' => Page::where('id', '!=', $page->id)->orderBy('title')->get(),
            'projectOptions' => Project::orderBy('title')->get(),
            'partnerOptions' => \App\Models\Partner::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    /** Zapisuje zmiany podstrony z walidacją i blokowaniem dostępu do zablokowanych stron. */
    public function update(Request $request, Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $page->id);

        $page->update($data);

        return redirect()->route('admin.podstrony.index')->with('status', 'Strona została zaktualizowana.');
    }

    /** Usuwa podstronę (stron systemowych nie można usunąć). */
    public function destroy(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        if ($page->is_system) {
            return redirect()->route('admin.podstrony.index')->with('error', 'Strony systemowej nie można usunąć.');
        }

        $page->delete();

        return redirect()->route('admin.podstrony.index')->with('status', 'Strona została usunięta.');
    }

    /** Przełącza widoczność podstrony (publikuj / ukryj). */
    public function toggleVisibility(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $page->update(['is_published' => ! $page->is_published]);

        $message = $page->is_published
            ? "Strona „{$page->title}” została opublikowana."
            : "Strona „{$page->title}” została ukryta.";

        return redirect()->route('admin.podstrony.index')->with('status', $message);
    }

    /** Przełącza wyróżnienie podstrony. */
    public function toggleFeatured(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $page->update(['is_featured' => ! $page->is_featured]);

        $message = $page->is_featured
            ? "Strona „{$page->title}” została wyróżniona."
            : "Usunięto wyróżnienie strony „{$page->title}”.";

        return redirect()->route('admin.podstrony.index')->with('status', $message);
    }

    /** Przełącza tryb wyłączenia podstrony (wyłącz / włącz ponownie). */
    public function toggleDisabled(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $page->update(['is_disabled' => ! $page->is_disabled]);

        $message = $page->is_disabled
            ? "Strona „{$page->title}” została wyłączona."
            : "Strona „{$page->title}” została ponownie włączona.";

        return redirect()->route('admin.podstrony.index')->with('status', $message);
    }

    /** Zmienia kolejność wyświetlania podstrony w menu/liście. */
    public function updateOrder(Request $request, Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $data = $request->validate([
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $page->update(['order' => $data['order']]);

        return redirect()->route('admin.podstrony.index')->with('status', "Zmieniono kolejność strony „{$page->title}”.");
    }

    /** Wykonuje zbiorczą operację (publikuj / cofnij / kosz) na zaznaczonych podstronach. */
    public function bulk(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,trash'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
        ]);

        $pages = Page::whereIn('id', $data['ids'])
            ->where('is_system', false)
            ->get();

        if ($pages->isEmpty()) {
            return redirect()->back()->with('error', 'Nie znaleziono stron do przetworzenia.');
        }

        $count = $pages->count();

        match ($data['action']) {
            'publish'   => Page::whereIn('id', $pages->pluck('id'))->update(['is_published' => true]),
            'unpublish' => Page::whereIn('id', $pages->pluck('id'))->update(['is_published' => false]),
            'trash'     => $pages->each->delete(),
        };

        $message = match ($data['action']) {
            'publish'   => "Opublikowano stron: {$count}.",
            'unpublish' => "Cofnięto publikację stron: {$count}.",
            'trash'     => "Przeniesiono do kosza stron: {$count}.",
        };

        return redirect()->back()->with('status', $message);
    }

    /** Klonuje podstronę jako szkic (bez flagi systemowej i blokady). */
    public function clone(Page $page)
    {
        if ($response = $this->denyIfLocked($page)) {
            return $response;
        }

        $clone = $page->replicate();
        $clone->title = "{$page->title} (kopia)";
        $clone->slug = $this->uniqueSlug($clone->title);
        $clone->is_published = false;
        $clone->is_system = false;
        $clone->is_locked = false;
        $clone->save();

        return redirect()->route('admin.podstrony.edit', $clone)->with('status', "Strona została sklonowana jako „{$clone->title}”. Jest zapisana jako szkic.");
    }

    /**
     * Zablokowanej treści nie mogą edytować osoby inne niż administrator.
     * Zwraca przekierowanie z komunikatem, gdy dostęp trzeba odmówić.
     */
    private function denyIfLocked(Page $page): ?\Illuminate\Http\RedirectResponse
    {
        if ($page->is_locked && ! request()->user()->isAdmin()) {
            return redirect()->route('admin.podstrony.index')
                ->with('error', "Strona „{$page->title}” została zablokowana do edycji przez administratora.");
        }

        return null;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:pages,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_display' => ['nullable', Rule::in(array_keys(Page::PROJECT_DISPLAYS))],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'content' => ['nullable', 'string'],
            'content_image' => ['nullable', 'string', 'max:1000'],
            'content_image_file' => ['nullable', 'image', 'max:8192'],
            'content_image_alt' => ['nullable', 'string', 'max:255'],
            'content_image_width' => ['nullable', 'string', 'max:30'],
            'remove_content_image' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'disabled_message' => ['nullable', 'string', 'max:2000'],
            'wip_mode' => ['nullable', Rule::in(array_keys(Page::WIP_MODES))],
            'wip_message' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Page::TYPES))],
            'access_mode' => ['nullable', Rule::in(array_keys(Page::ACCESS_MODES))],
            'access_password' => ['nullable', 'string', 'max:255'],
            'hub_hero' => ['nullable', 'string', 'max:1000'],
            'hub_hero_file' => ['nullable', 'image', 'max:4096'],
            'hub_intro' => ['nullable', 'string', 'max:2000'],
            'hub_links' => ['nullable', 'array'],
            'hub_links.*.label' => ['nullable', 'string', 'max:120'],
            'hub_links.*.url' => ['nullable', 'string', 'max:500'],
            'hub_links.*.description' => ['nullable', 'string', 'max:255'],
            'hub_links.*.icon' => ['nullable', 'string', 'max:100'],
            'legacy_name' => ['nullable', 'string', 'max:255'],
            'legacy_intro' => ['nullable', 'string', 'max:2000'],
            'event_mode' => ['nullable', Rule::in(array_keys(Page::EVENT_MODES))],
            'event_when' => ['nullable', 'string', 'max:255'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'event_how_to_join' => ['nullable', 'string', 'max:2000'],
            'event_registration_url' => ['nullable', 'url', 'max:255'],
            'schedule_change_notice' => ['nullable', 'string', 'max:2000'],
            'schedule_items' => ['nullable', 'array'],
            'schedule_items.*.date' => ['nullable', 'string', 'max:255'],
            'schedule_items.*.time' => ['nullable', 'string', 'max:255'],
            'schedule_items.*.location' => ['nullable', 'string', 'max:255'],
            'schedule_items.*.note' => ['nullable', 'string', 'max:500'],
            'about_motto' => ['nullable', 'string', 'max:1000'],
            'about_motto_author' => ['nullable', 'string', 'max:255'],
            'about_intro' => ['nullable', 'string', 'max:5000'],
            'about_stats' => ['nullable', 'array'],
            'about_stats.*.value' => ['nullable', 'string', 'max:50'],
            'about_stats.*.label' => ['nullable', 'string', 'max:120'],
            'about_timeline' => ['nullable', 'array'],
            'about_timeline.*.year' => ['nullable', 'string', 'max:20'],
            'about_timeline.*.text' => ['nullable', 'string', 'max:500'],
            'about_timeline.*.url' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.url2' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label2' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.url3' => ['nullable', 'string', 'max:255'],
            'about_timeline.*.label3' => ['nullable', 'string', 'max:120'],
            'about_timeline.*.color' => ['nullable', 'string', 'max:7'],
            'about_values' => ['nullable', 'array'],
            'about_values.*.icon' => ['nullable', 'string', 'max:100'],
            'about_values.*.title' => ['nullable', 'string', 'max:120'],
            'about_values.*.text' => ['nullable', 'string', 'max:500'],
            'about_team' => ['nullable', 'array'],
            'about_team.*.name' => ['nullable', 'string', 'max:120'],
            'about_team.*.role' => ['nullable', 'string', 'max:120'],
            'about_team.*.photo' => ['nullable', 'string', 'max:1000'],
            'about_team.*.bio' => ['nullable', 'string', 'max:1000'],
            'about_team.*.facebook' => ['nullable', 'string', 'max:255'],
            'about_team.*.instagram' => ['nullable', 'string', 'max:255'],
            'about_team.*.linkedin' => ['nullable', 'string', 'max:255'],
            'about_team.*.website' => ['nullable', 'string', 'max:255'],
            'about_team.*.substack' => ['nullable', 'string', 'max:255'],
            'about_team_photos' => ['nullable', 'array'],
            'about_team_photos.*' => ['nullable', 'image', 'max:4096'],
            'about_section_order' => ['sometimes', 'array'],
            'about_section_order.*' => ['integer'],
            'about_partner_ids' => ['nullable', 'array'],
            'about_partner_ids.*' => ['integer', 'exists:partners,id'],
            'about_faq_visible' => ['sometimes', 'boolean'],
            'about_documents_intro' => ['nullable', 'string', 'max:5000'],
            'about_documents_bip_url' => ['nullable', 'string', 'max:255'],
            'about_press_intro' => ['nullable', 'string', 'max:5000'],
            'about_press' => ['nullable', 'array'],
            'about_press.*.url' => ['nullable', 'string', 'max:500'],
            'about_press.*.title' => ['nullable', 'string', 'max:255'],
            'about_press.*.source' => ['nullable', 'string', 'max:120'],
            'about_press.*.image' => ['nullable', 'string', 'max:1000'],
            'faq_intro' => ['nullable', 'string', 'max:2000'],
            'faq_items' => ['nullable', 'array'],
            'faq_items.*.question' => ['nullable', 'string', 'max:255'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:20000'],
            'bip_move_url' => ['nullable', 'url', 'max:255'],
            'bip_move_note' => ['nullable', 'string', 'max:2000'],
            'training_manager_name' => ['nullable', 'string', 'max:255'],
            'training_manager_title' => ['nullable', 'string', 'max:255'],
            'training_ris_number' => ['nullable', 'string', 'max:100'],
            'training_bur_number' => ['nullable', 'string', 'max:100'],
            'training_extra_info' => ['nullable', 'string', 'max:10000'],
            'training_bur_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['project_id'] = $data['project_id'] ?: null;
        $data['project_display'] = $data['project_id'] ? ($data['project_display'] ?? 'link') : 'link';
        $data['slug'] = trim($data['slug'] ?? '');
        $data['is_published'] = $request->boolean('is_published');
        $data['publish_at'] = $request->filled('publish_at') ? $request->input('publish_at') : null;
        $data['is_archived'] = $request->boolean('is_archived');
        $data['show_in_menu'] = $request->boolean('show_in_menu');
        $data['show_side_nav'] = $request->boolean('show_side_nav');
        $data['is_system'] = $request->boolean('is_system');
        $data['show_gallery'] = $request->boolean('show_gallery');
        // Flagę „zablokuj do edycji” może ustawiać/zdejmować wyłącznie administrator.
        // Dla pozostałych nie dotykamy jej (przy tworzeniu = false, przy edycji zachowana).
        if ($request->user()->isAdmin()) {
            $data['is_locked'] = $request->boolean('is_locked');
        }
        $data['order'] = $data['order'] ?? 0;

        // Availability controls: "disable page" and "under construction" mode.
        $data['is_disabled'] = $request->boolean('is_disabled');
        $data['disabled_message'] = trim((string) ($data['disabled_message'] ?? '')) ?: null;
        $data['wip_mode'] = $data['wip_mode'] ?? null;
        // A message without a mode selected would never surface — drop it.
        $data['wip_message'] = $data['wip_mode']
            ? (trim((string) ($data['wip_message'] ?? '')) ?: null)
            : null;

        // Event details only apply to event pages; clear them otherwise so a
        // page switched back to "standard" doesn't keep stale event data.
        if ($data['type'] !== 'event') {
            $data['event_mode'] = null;
            $data['event_when'] = null;
            $data['event_location'] = null;
            $data['event_how_to_join'] = null;
            $data['event_registration_url'] = null;
        }

        // Schedule details only apply to schedule pages. Build the ordered list
        // of entries, dropping rows where every field is empty, and clear
        // everything when the page is not a schedule.
        if ($data['type'] === 'schedule') {
            $items = [];
            foreach ((array) $request->input('schedule_items', []) as $row) {
                $date = trim((string) ($row['date'] ?? ''));
                $time = trim((string) ($row['time'] ?? ''));
                $location = trim((string) ($row['location'] ?? ''));
                $note = trim((string) ($row['note'] ?? ''));

                if ($date === '' && $time === '' && $location === '' && $note === '') {
                    continue;
                }

                $items[] = [
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'note' => $note,
                    'changed' => ! empty($row['changed']),
                ];
            }
            $data['schedule_items'] = $items ?: null;
            $data['schedule_pending'] = $request->boolean('schedule_pending');
        } else {
            $data['schedule_items'] = null;
            $data['schedule_change_notice'] = null;
            $data['schedule_pending'] = false;
        }

        // "O organizacji" details only apply to about pages. Build the repeater
        // sections (dropping fully-empty rows) and clear everything otherwise.
        if ($data['type'] === 'about') {
            $data['about_stats'] = $this->compactRows($request->input('about_stats', []), ['value', 'label']);
            $data['about_timeline'] = $this->compactRows($request->input('about_timeline', []), ['year', 'text', 'url', 'label', 'url2', 'label2', 'url3', 'label3', 'color']);
            $data['about_values'] = $this->compactRows($request->input('about_values', []), ['icon', 'title', 'text']);

            // Zdjęcia zespołu: wgrane pliki (mapowane po indeksie wiersza) mają
            // pierwszeństwo nad ręcznie wpisanym URL. Robimy to PRZED compactRows,
            // dopóki indeksy plików pasują do surowych wierszy formularza.
            $teamRows = $request->input('about_team', []);
            foreach ((array) $request->file('about_team_photos', []) as $index => $file) {
                if ($file && isset($teamRows[$index])) {
                    $teamRows[$index]['photo'] = \Illuminate\Support\Facades\Storage::disk('public')->url(
                        $file->store('zespol', 'public')
                    );
                }
            }
            $data['about_team'] = $this->compactRows($teamRows, ['name', 'role', 'photo', 'bio', 'facebook', 'instagram', 'linkedin', 'website', 'substack']);

            $positions = $request->input('about_section_order', []);
            $data['about_section_order'] = collect(array_keys(Page::ABOUT_SECTIONS))
                ->sortBy(fn ($key) => $positions[$key] ?? 999)
                ->values()
                ->all();

            $data['about_partner_ids'] = array_values(array_map('intval', (array) $request->input('about_partner_ids', []))) ?: null;
            $data['about_faq_visible'] = $request->boolean('about_faq_visible');

            // „Piszą o nas": wzmianki prasowe. Dla wpisów z linkiem, ale bez
            // obrazka/tytułu, pobieramy je ze strony (og:image / og:title).
            $press = $this->compactRows($request->input('about_press', []), ['url', 'title', 'source', 'image']);
            foreach (($press ?? []) as $i => $item) {
                if (! empty($item['url']) && (empty($item['image']) || empty($item['title']))) {
                    $og = $this->scrapeOgData($item['url']);
                    if (empty($item['image']) && ! empty($og['image'])) {
                        $press[$i]['image'] = $og['image'];
                    }
                    if (empty($item['title']) && ! empty($og['title'])) {
                        $press[$i]['title'] = $og['title'];
                    }
                }
            }
            $data['about_press'] = $press;
        } else {
            $data['about_motto'] = null;
            $data['about_motto_author'] = null;
            $data['about_intro'] = null;
            $data['about_section_order'] = null;
            $data['about_stats'] = null;
            $data['about_timeline'] = null;
            $data['about_values'] = null;
            $data['about_team'] = null;
            $data['about_partner_ids'] = null;
            $data['about_faq_page_id'] = null;
            $data['about_documents_intro'] = null;
            $data['about_documents_bip_url'] = null;
            $data['about_press_intro'] = null;
            $data['about_press'] = null;
        }

        // FAQ: lista par pytanie/odpowiedź (puste wiersze pomijane); poza typem FAQ czyścimy.
        if ($data['type'] === 'faq') {
            $data['faq_items'] = $this->compactRows($request->input('faq_items', []), ['question', 'answer']);
        } else {
            $data['faq_intro'] = null;
            $data['faq_items'] = null;
        }

        // Bip-Move: komunikat o przeniesieniu do BIP; poza typem czyścimy pola.
        if ($data['type'] !== 'bip_move') {
            $data['bip_move_url'] = null;
            $data['bip_move_note'] = null;
        } else {
            $data['bip_move_url'] = trim((string) ($data['bip_move_url'] ?? '')) ?: null;
            $data['bip_move_note'] = trim((string) ($data['bip_move_note'] ?? '')) ?: null;
        }

        // Wewnętrzna: tryb dostępu + hasło. Puste hasło przy edycji = bez zmian
        // (usuwamy klucz, by nie nadpisać). Poza tym typem czyścimy dostęp.
        $newPassword = trim((string) ($data['access_password'] ?? ''));
        unset($data['access_password']);

        if (in_array($data['type'], ['internal', 'internal_hub'], true)) {
            $data['access_mode'] = $data['access_mode'] ?? 'password';

            if ($data['access_mode'] === 'microsoft') {
                $data['access_password'] = null; // logowanie zamiast hasła
            } elseif ($newPassword !== '') {
                $data['access_password'] = \Illuminate\Support\Facades\Hash::make($newPassword);
            }
            // password puste przy edycji → klucz nieobecny → hasło bez zmian.
        } else {
            $data['access_mode'] = null;
            $data['access_password'] = null;
        }

        // Panel współpracownika: hero (wgrany plik ma pierwszeństwo nad URL),
        // wstęp i kafelki linków. Poza tym subtypem czyścimy pola.
        if ($data['type'] === 'internal_hub') {
            if ($request->hasFile('hub_hero_file')) {
                $data['hub_hero'] = \Illuminate\Support\Facades\Storage::disk('public')->url(
                    $request->file('hub_hero_file')->store('panel', 'public')
                );
            } else {
                $data['hub_hero'] = trim((string) ($data['hub_hero'] ?? '')) ?: null;
            }
            $data['hub_intro'] = trim((string) ($data['hub_intro'] ?? '')) ?: null;
            $data['hub_links'] = $this->compactRows($request->input('hub_links', []), ['label', 'url', 'description', 'icon']);
        } else {
            $data['hub_hero'] = null;
            $data['hub_intro'] = null;
            $data['hub_links'] = null;
        }

        unset($data['hub_hero_file']);

        // Zdjęcie w treści: wgrany plik ma pierwszeństwo nad URL; checkbox usuwa.
        if ($request->hasFile('content_image_file')) {
            $data['content_image'] = \Illuminate\Support\Facades\Storage::disk('public')->url(
                $request->file('content_image_file')->store('pages', 'public')
            );
        } elseif ($request->boolean('remove_content_image')) {
            $data['content_image'] = null;
        } else {
            $data['content_image'] = trim((string) ($data['content_image'] ?? '')) ?: null;
        }
        $data['content_image_alt'] = trim((string) ($data['content_image_alt'] ?? '')) ?: null;
        $data['content_image_width'] = trim((string) ($data['content_image_width'] ?? '')) ?: null;
        unset($data['content_image_file'], $data['remove_content_image']);

        // „Prezentacja tego, co było": nazwa poprzednika + wstęp; poza typem czyścimy.
        if ($data['type'] === 'legacy') {
            $data['legacy_name'] = trim((string) ($data['legacy_name'] ?? '')) ?: null;
            $data['legacy_intro'] = trim((string) ($data['legacy_intro'] ?? '')) ?: null;
        } else {
            $data['legacy_name'] = null;
            $data['legacy_intro'] = null;
        }

        return $this->applyApprovalWorkflow($data);
    }

    /**
     * Trim a repeater's rows to the given keys and drop any row whose fields are
     * all empty. Returns null when nothing is left, so the JSON column stays clean.
     */
    private function compactRows($rows, array $keys): ?array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            $clean = [];
            foreach ($keys as $key) {
                $clean[$key] = trim((string) ($row[$key] ?? ''));
            }
            if (implode('', $clean) !== '') {
                $out[] = $clean;
            }
        }

        return $out ?: null;
    }

    /**
     * Pobierz og:image i og:title ze wskazanego adresu (sekcja „Piszą o nas”).
     * Odporne na błędy — przy niepowodzeniu zwraca puste wartości.
     */
    private function scrapeOgData(string $url): array
    {
        $empty = ['image' => null, 'title' => null];

        if (! preg_match('#^https?://#i', $url)) {
            return $empty;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(6)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; FEERbot/1.0; +og-scraper)'])
                ->get($url);

            if (! $response->ok()) {
                return $empty;
            }

            $html = $response->body();

            return [
                'image' => $this->metaContent($html, ['og:image', 'twitter:image', 'twitter:image:src']),
                'title' => $this->metaContent($html, ['og:title', 'twitter:title']),
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * Wyłuskaj zawartość pierwszego pasującego <meta> (property lub name) z HTML.
     */
    private function metaContent(string $html, array $keys): ?string
    {
        foreach ($keys as $key) {
            $k = preg_quote($key, '#');
            if (preg_match('#<meta[^>]+(?:property|name)=["\']'.$k.'["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)
                || preg_match('#<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']'.$k.'["\']#i', $html, $m)) {
                $value = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'strona';
        $slug = $base;
        $suffix = 2;

        $isTaken = fn (string $candidate) => in_array($candidate, Page::RESERVED_SLUGS, true)
            || Page::where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();

        while ($isTaken($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
