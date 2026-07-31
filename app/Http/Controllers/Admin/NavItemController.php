<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NavItemController extends Controller
{
    public function index(Request $request)
    {
        $location = $request->input('location', 'main');
        $location = array_key_exists($location, NavItem::LOCATIONS) ? $location : 'main';

        $navItems = NavItem::where('location', $location)
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('order')
            ->get();

        return view('admin.nav-items.index', [
            'navItems' => $navItems,
            'location' => $location,
            // Wszystkie możliwe pozycje-rodzice dla współdzielonego modala edycji
            // (menu główne). Wykluczenie „samego siebie" odbywa się po stronie
            // klienta na podstawie edytowanego identyfikatora.
            'parentOptions' => $this->parentOptions(null, 'main'),
            'pages' => Page::orderBy('title')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $location = $request->input('location', 'main');
        $location = array_key_exists($location, NavItem::LOCATIONS) ? $location : 'main';

        return view('admin.nav-items.form', [
            'navItem' => new NavItem(['location' => $location]),
            'parentOptions' => $this->parentOptions(null, $location),
            'pages' => Page::orderBy('title')->get(),
        ]);
    }

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

        NavItem::create($data);

        return redirect()->route('admin.pozycje-menu.index', ['location' => $data['location']])->with('status', 'Pozycja menu została dodana.');
    }

    public function edit(NavItem $navItem)
    {
        return view('admin.nav-items.form', [
            'navItem' => $navItem,
            'parentOptions' => $this->parentOptions($navItem, $navItem->location),
            'pages' => Page::orderBy('title')->get(),
        ]);
    }

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

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location])
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

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location])
            ->with($status['ok'] ? 'status' : 'error', $status['message'])
            ->with('focus_nav', $navItem->id);
    }

    public function destroy(NavItem $navItem)
    {
        $location = $navItem->location;
        $navItem->delete();

        return redirect()->route('admin.pozycje-menu.index', ['location' => $location])->with('status', 'Pozycja menu została usunięta.');
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
        return NavItem::whereIn('type', ['dropdown', 'link'])
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
            'url' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(NavItem::TYPES))],
            'location' => ['required', Rule::in(array_keys(NavItem::LOCATIONS))],
            'module' => ['nullable', Rule::in(array_keys(SiteSetting::MODULES))],
            'parent_id' => [
                'nullable',
                // Uwaga: w regule exists używamy 0 zamiast false — wartość false
                // binduje się w weryfikatorze obecności jako '' i reguła zawsze zawodzi.
                Rule::exists('nav_items', 'id')->whereIn('type', ['dropdown', 'link'])->where('is_button', 0)->whereNull('parent_id'),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
            'button_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
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

        // The footer only ever renders plain links — no dropdowns/submenus.
        if ($data['location'] === 'footer') {
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

        $data['is_transparent_dropdown'] = $request->boolean('is_transparent_dropdown');
        $data['is_active'] = $request->boolean('is_active');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
