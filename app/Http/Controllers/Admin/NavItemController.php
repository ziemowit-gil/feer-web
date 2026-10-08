<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BipDocument;
use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Panel admin: zarządzanie pozycjami menu (główne i stopka) z reorderingiem
 * drag & drop i przesuwaniem przyciskami w górę/dół.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), move(), reorder().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NavItemController extends Controller
{
    /** Wyświetla listę pozycji menu dla danej lokalizacji (main/footer). */
    public function index(Request $request)
    {
        $location = $request->input('location', 'main');
        $location = array_key_exists($location, NavItem::LOCATIONS) ? $location : 'main';

        $navItems = NavItem::forCurrentSite()->where('location', $location)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get();

        // Wybrana pozycja (?pozycja=ID) — prawy panel pokazuje jej szczegóły; domyślnie pierwsza.
        $flat = $navItems->flatMap(fn (NavItem $i) => collect([$i])->concat($i->allChildren));
        $selected = $flat->firstWhere('id', (int) $request->query('pozycja')) ?? $navItems->first();

        return view('admin.nav-items.index', [
            'navItems' => $navItems,
            'selected' => $selected,
            'location' => $location,
            // Wszystkie możliwe pozycje-rodzice dla współdzielonego modala edycji
            // (menu główne). Wykluczenie „samego siebie" odbywa się po stronie
            // klienta na podstawie edytowanego identyfikatora.
            'parentOptions' => $this->parentOptions(null, 'main'),
            'pages' => Page::orderBy('title')->get(),
            'bipDocuments' => BipDocument::published()->orderBy('title')->get(),
        ]);
    }

    /** Wyświetla formularz tworzenia nowej pozycji menu. */
    public function create(Request $request)
    {
        $location = $request->input('location', 'main');
        $location = array_key_exists($location, NavItem::LOCATIONS) ? $location : 'main';

        return view('admin.nav-items.form', [
            'navItem' => new NavItem(['location' => $location]),
            'parentOptions' => $this->parentOptions(null, $location),
            'pages' => Page::orderBy('title')->get(),
            'bipDocuments' => BipDocument::published()->orderBy('title')->get(),
        ]);
    }

    /** Zapisuje nową pozycję menu, umieszczając ją na końcu listy rodzeństwa. */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Nowa pozycja bez podanej kolejności trafia na koniec swojej grupy
        // rodzeństwa (rodzic + lokalizacja), żeby nie kolidowała z istniejącymi.
        if (! $request->filled('order')) {
            $data['order'] = 1 + (int) NavItem::where('location', $data['location'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('order');
        }

        $created = NavItem::create($data);

        return redirect()->route('admin.pozycje-menu.index', ['location' => $data['location'], 'pozycja' => $created->id])->with('status', 'Pozycja menu została dodana.');
    }

    /** Wyświetla formularz edycji pozycji menu. */
    public function edit(NavItem $navItem)
    {
        return view('admin.nav-items.form', [
            'navItem' => $navItem,
            'parentOptions' => $this->parentOptions($navItem, $navItem->location),
            'pages' => Page::orderBy('title')->get(),
            'bipDocuments' => BipDocument::published()->orderBy('title')->get(),
        ]);
    }

    /** Aktualizuje pozycję menu z zabezpieczeniem przed niepoprawnym zagnieżdżaniem. */
    public function update(Request $request, NavItem $navItem)
    {
        $data = $this->validated($request);

        // Pozycja nie może być własnym rodzicem, a pozycja mająca własne
        // podpozycje nie może stać się podpozycją (menu ma tylko jeden poziom
        // zagnieżdżenia — inaczej powstałyby „wnuki", których szablon nie renderuje).
        if (($data['parent_id'] ?? null) == $navItem->id
            || (($data['parent_id'] ?? null) && $navItem->allChildren()->exists())) {
            $data['parent_id'] = null;
        }

        $navItem->update($data);

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location, 'pozycja' => $navItem->id])
            ->with('status', 'Pozycja menu została zaktualizowana.')
            ->with('focus_nav', $navItem->id);
    }

    /**
     * Dostępna alternatywa dla Drag & Drop: przenoszenie pozycji przyciskami.
     * Obsługuje cztery działania w obrębie menu — w górę / w dół (kolejność
     * wśród rodzeństwa) oraz zagnieżdżenie / wysunięcie (zmiana poziomu).
     */
    public function move(Request $request, NavItem $navItem)
    {
        $action = $request->validate([
            'action' => ['required', Rule::in(['up', 'down', 'indent', 'outdent'])],
        ])['action'];

        $status = match ($action) {
            'up', 'down' => $this->reorderSibling($navItem, $action),
            'indent' => $this->indent($navItem),
            'outdent' => $this->outdent($navItem),
        };

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location, 'pozycja' => $navItem->id])
            ->with($status['ok'] ? 'status' : 'error', $status['message'])
            ->with('focus_nav', $navItem->id);
    }

    /**
     * Zapisuje nową kolejność i zagnieżdżenie po drag & drop.
     * Przyjmuje tablicę {id, parent_id, order} dla wszystkich pozycji menu
     * w danej lokalizacji — klient wysyła kompletny snapshot, nie delty.
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'items'            => ['required', 'array', 'min:1'],
            'items.*.id'       => ['required', 'integer', 'exists:nav_items,id'],
            'items.*.parent_id'=> ['nullable', 'integer', 'exists:nav_items,id'],
            'items.*.order'    => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                NavItem::where('id', $item['id'])->update([
                    'parent_id' => $item['parent_id'] ?? null,
                    'order'     => $item['order'],
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /** Przełącza widoczność pozycji w menu (ukryj / pokaż) bez otwierania formularza. */
    public function toggleActive(NavItem $navItem)
    {
        $navItem->update(['is_active' => ! $navItem->is_active]);

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location, 'pozycja' => $navItem->id])
            ->with('status', $navItem->is_active ? "Pozycja „{$navItem->label}” jest widoczna w menu." : "Pozycja „{$navItem->label}” została ukryta w menu.");
    }

    /** Usuwa pozycję menu. */
    public function destroy(NavItem $navItem)
    {
        $location = $navItem->location;
        $parentId = $navItem->parent_id;
        $navItem->delete();

        // Po usunięciu podpozycji wracamy do jej pozycji nadrzędnej.
        return redirect()->route('admin.pozycje-menu.index', array_filter(['location' => $location, 'pozycja' => $parentId]))->with('status', 'Pozycja menu została usunięta.');
    }

    /**
     * Przesuwa pozycję w górę/w dół wśród rodzeństwa (ta sama lokalizacja i ten
     * sam rodzic), po czym porządkuje kolejność jako 0..n.
     */
    private function reorderSibling(NavItem $navItem, string $direction): array
    {
        $ids = $this->siblingIds($navItem->location, $navItem->parent_id);
        $pos = array_search($navItem->id, $ids, true);
        $target = $direction === 'up' ? $pos - 1 : $pos + 1;

        if ($pos === false || $target < 0 || $target >= count($ids)) {
            return ['ok' => false, 'message' => 'Pozycja jest już na skraju listy — nie można jej przesunąć w tym kierunku.'];
        }

        [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];
        $this->applyOrder($ids);

        return ['ok' => true, 'message' => "Przeniesiono „{$navItem->label}” {$this->directionLabel($direction)}."];
    }

    /**
     * Zagnieżdża pozycję najwyższego poziomu jako podpozycję poprzedzającego ją
     * rodzeństwa (jeśli to rodzeństwo może mieć podpozycje).
     */
    private function indent(NavItem $navItem): array
    {
        if ($navItem->parent_id !== null) {
            return ['ok' => false, 'message' => 'Pozycja jest już podpozycją — menu ma tylko jeden poziom zagnieżdżenia.'];
        }

        if (! $this->isNestableLeaf($navItem)) {
            return ['ok' => false, 'message' => 'Tylko zwykły link bez własnych podpozycji można zagnieździć.'];
        }

        $ids = $this->siblingIds($navItem->location, null);
        $pos = array_search($navItem->id, $ids, true);

        if ($pos === false || $pos === 0) {
            return ['ok' => false, 'message' => 'Brak pozycji powyżej, w której można zagnieździć tę pozycję.'];
        }

        $previous = NavItem::find($ids[$pos - 1]);

        if (! $this->canHoldChildren($previous)) {
            return ['ok' => false, 'message' => "Pozycja „{$previous->label}” nie może zawierać podpozycji."];
        }

        $navItem->update([
            'parent_id' => $previous->id,
            'order' => 1 + (int) NavItem::where('parent_id', $previous->id)->max('order'),
        ]);

        $this->resequence($navItem->location, null);
        $this->resequence($navItem->location, $previous->id);

        return ['ok' => true, 'message' => "Zagnieżdżono „{$navItem->label}” w „{$previous->label}”."];
    }

    /**
     * Wysuwa podpozycję na najwyższy poziom, umieszczając ją tuż za jej byłym
     * rodzicem.
     */
    private function outdent(NavItem $navItem): array
    {
        if ($navItem->parent_id === null) {
            return ['ok' => false, 'message' => 'Pozycja jest już na najwyższym poziomie.'];
        }

        $parent = $navItem->parent;
        $oldParentId = $navItem->parent_id;

        $top = $this->siblingIds($navItem->location, null);
        $parentPos = array_search($parent->id, $top, true);
        array_splice($top, $parentPos === false ? count($top) : $parentPos + 1, 0, [$navItem->id]);

        $navItem->update(['parent_id' => null]);
        $this->applyOrder($top);
        $this->resequence($navItem->location, $oldParentId);

        return ['ok' => true, 'message' => "Wysunięto „{$navItem->label}” na najwyższy poziom."];
    }

    /**
     * Identyfikatory rodzeństwa (ta sama lokalizacja i rodzic) w kolejności.
     *
     * @return array<int, int>
     */
    private function siblingIds(string $location, ?int $parentId): array
    {
        return NavItem::where('location', $location)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * Nadaje pozycjom kolejność 0..n wg podanej tablicy identyfikatorów.
     *
     * @param  array<int, int>  $ids
     */
    private function applyOrder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            NavItem::where('id', $id)->update(['order' => $index]);
        }
    }

    private function resequence(string $location, ?int $parentId): void
    {
        $this->applyOrder($this->siblingIds($location, $parentId));
    }

    /**
     * Czy pozycja może przyjąć podpozycje: tylko „Rozwijane menu" lub zwykły
     * link (nie przycisk CTA) na najwyższym poziomie menu głównego.
     */
    private function canHoldChildren(?NavItem $item): bool
    {
        return $item !== null
            && $item->location === 'main'
            && $item->parent_id === null
            && ! $item->is_button
            && in_array($item->type, ['dropdown', 'link'], true);
    }

    /**
     * Czy pozycję można zagnieździć: zwykły link (nie przycisk) bez własnych
     * podpozycji, w menu głównym.
     */
    private function isNestableLeaf(NavItem $item): bool
    {
        return $item->location === 'main'
            && $item->type === 'link'
            && ! $item->is_button
            && ! $item->allChildren()->exists();
    }

    private function directionLabel(string $direction): string
    {
        return $direction === 'up' ? 'w górę' : 'w dół';
    }

    /**
     * Only top-level "dropdown" items can hold children, and only one level
     * deep — a dropdown/projects item can't itself be nested. Footer items
     * never nest (the footer only ever renders plain links).
     */
    private function parentOptions(?NavItem $editing = null, string $location = 'main')
    {
        if ($location !== 'main') {
            return collect();
        }

        // Rodzicem podpozycji może być „Rozwijane menu" albo zwykły link
        // (np. do istniejącej strony) — ale nie przycisk CTA.
        return NavItem::whereIn('type', ['dropdown', 'link', 'projects'])
            ->where('is_button', false)
            ->where('location', 'main')
            ->whereNull('parent_id')
            ->when($editing?->exists, fn ($query) => $query->where('id', '!=', $editing->id))
            ->orderBy('order')
            ->get();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'mega_image_url' => ['nullable', 'string', 'max:500'],
            'mega_image_file' => ['nullable', 'image', 'max:4096'],
            'mega_image_alt' => ['nullable', 'string', 'max:255'],
            'is_column_heading' => ['nullable', 'boolean'],
            'mega_size' => ['nullable', Rule::in(array_keys(NavItem::MEGA_SIZES))],
            'mega_extra_title' => ['nullable', 'string', 'max:80'],
            'mega_side_title' => ['nullable', 'string', 'max:80'],
            'mega_side_links' => ['nullable', 'array', 'max:8'],
            'mega_side_links.*.label' => ['nullable', 'string', 'max:80'],
            // Bez schematów wykonywalnych (javascript:, data:, vbscript:).
            'mega_side_links.*.url' => ['nullable', 'string', 'max:500', 'not_regex:/^\s*(javascript|data|vbscript):/i'],
            'mega_side_links.*.style' => ['nullable', Rule::in(NavItem::SIDE_LINK_STYLES)],
            'mega_side_links.*.new_tab' => ['nullable', 'boolean'],
            'url' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(NavItem::TYPES))],
            'location' => ['required', Rule::in(array_keys(NavItem::LOCATIONS))],
            'module' => ['nullable', Rule::in(array_keys(SiteSetting::MODULES))],
            'parent_id' => [
                'nullable',
                // Uwaga: w regule exists używamy 0 zamiast false — wartość false
                // binduje się w weryfikatorze obecności jako '' i reguła zawsze zawodzi.
                Rule::exists('nav_items', 'id')->whereIn('type', ['dropdown', 'link', 'projects'])->where('is_button', 0)->whereNull('parent_id'),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
            'button_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $data['url'] = ($data['url'] ?? null) ?: '#';

        // Typ „Ogłoszenia o wolontariacie" kieruje zawsze na listę /wolontariat
        // i automatycznie chowa się, gdy moduł wolontariatu jest wyłączony.
        // Renderuje się jak zwykły link (może być przyciskiem CTA).
        if ($data['type'] === 'volunteering') {
            $data['url'] = route('volunteer.index');
            $data['module'] = 'volunteering';
        }

        // Typ „Szkolenia i wydarzenia" kieruje zawsze na listę /wydarzenia
        // i chowa się, gdy moduł wydarzeń jest wyłączony (jak wolontariat).
        if ($data['type'] === 'events') {
            $data['url'] = route('events.index');
            $data['module'] = 'events';
        }

        // Typ „FAQ" kieruje zawsze na stronę /faq i chowa się, gdy moduł wyłączony.
        if ($data['type'] === 'faq') {
            $data['url'] = route('faq.index');
            $data['module'] = 'faq';
        }

        // Footer and BIP sidebar only render plain links — no dropdowns/submenus.
        if (in_array($data['location'], ['footer', 'bip'], true)) {
            $data['type'] = 'link';
            $data['parent_id'] = null;
        }

        // Dropdown/projects triggers open a panel instead of navigating, and
        // can't themselves be nested inside another dropdown. The volunteering
        // type is always a top-level link/CTA, so it can't be nested either.
        if (in_array($data['type'], ['dropdown', 'projects', 'volunteering', 'events', 'faq'], true)) {
            $data['parent_id'] = null;
        }

        // A child link renders as a plain row inside its parent's panel, so
        // the CTA button style only makes sense for top-level items.
        $data['is_button'] = ($data['parent_id'] ?? null) ? false : $request->boolean('is_button');

        // A custom colour only applies to CTA buttons; drop it otherwise so a
        // toggled-off button doesn't keep a stray colour.
        if (! $data['is_button']) {
            $data['button_color'] = null;
        }

        // Nagłówek kolumny mega menu ma sens tylko dla podpozycji (menu głównego).
        $data['is_column_heading'] = ($data['parent_id'] ?? null) && $data['location'] === 'main'
            ? $request->boolean('is_column_heading')
            : false;

        $data['is_transparent_dropdown'] = $request->boolean('is_transparent_dropdown');
        // Domyślne przyciski menu projektów można wyłączyć (tylko dla typu „Menu projektów").
        $data['hide_all_projects_btn'] = $data['type'] === 'projects' && $request->boolean('hide_all_projects_btn');
        $data['hide_archive_btn'] = $data['type'] === 'projects' && $request->boolean('hide_archive_btn');
        // Mega menu ma sens tylko dla pozycji głównych typu rozwijane menu lub link.
        $data['is_mega'] = ! ($data['parent_id'] ?? null) && in_array($data['type'], ['dropdown', 'link', 'projects'], true)
            ? $request->boolean('is_mega')
            : false;

        // Grafika promocyjna mega menu: wgrany plik ma pierwszeństwo przed adresem;
        // „usuń" czyści; bez mega menu nie trzymamy osieroconej grafiki.
        unset($data['mega_image_url'], $data['mega_image_file']);
        $data['mega_size'] = $data['mega_size'] ?? 'md';
        // Nagłówek dodatkowej kolumny ma sens tylko dla menu projektów.
        $data['mega_extra_title'] = $data['type'] === 'projects' ? (trim((string) ($data['mega_extra_title'] ?? '')) ?: null) : null;
        // Karta boczna (tytuł + własne linki/przyciski) istnieje tylko przy włączonym mega menu.
        $sideLinks = collect($request->input('mega_side_links', []))
            ->map(fn ($l) => [
                'label' => trim((string) ($l['label'] ?? '')),
                'url' => trim((string) ($l['url'] ?? '')),
                'style' => in_array($l['style'] ?? 'link', NavItem::SIDE_LINK_STYLES, true) ? $l['style'] : 'link',
                'new_tab' => filter_var($l['new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->filter(fn ($l) => $l['label'] !== '' && $l['url'] !== '')
            ->values()
            ->all();
        // Własne linki/przyciski dotyczą każdego rozwijanego menu głównego (mega i zwykłego).
        $hasSideCard = $data['is_mega'] || in_array($data['type'], ['dropdown', 'projects', 'pages'], true);
        $data['mega_side_links'] = $hasSideCard && $sideLinks ? $sideLinks : null;
        $data['mega_side_title'] = $hasSideCard ? (trim((string) ($data['mega_side_title'] ?? '')) ?: null) : null;

        if (! $data['is_mega'] || $request->boolean('remove_mega_image')) {
            $data['mega_image'] = null;
            $data['mega_image_alt'] = null;
        } elseif ($request->hasFile('mega_image_file')) {
            $data['mega_image'] = \Illuminate\Support\Facades\Storage::disk('public')->url(
                $request->file('mega_image_file')->store('menu', 'public')
            );
        } elseif ($request->filled('mega_image_url')) {
            $data['mega_image'] = trim((string) $request->input('mega_image_url'));
        }
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
