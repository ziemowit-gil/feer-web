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

        return view('admin.nav-items.index', compact('navItems', 'location'));
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
        NavItem::create($this->validated($request));

        return redirect()->route('admin.pozycje-menu.index', ['location' => $request->input('location', 'main')])->with('status', 'Pozycja menu została dodana.');
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
        $navItem->update($this->validated($request));

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location])->with('status', 'Pozycja menu została zaktualizowana.');
    }

    public function updateOrder(Request $request, NavItem $navItem)
    {
        $data = $request->validate([
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $navItem->update(['order' => $data['order']]);

        return redirect()->route('admin.pozycje-menu.index', ['location' => $navItem->location])->with('status', "Zmieniono kolejność pozycji „{$navItem->label}”.");
    }

    public function destroy(NavItem $navItem)
    {
        $location = $navItem->location;
        $navItem->delete();

        return redirect()->route('admin.pozycje-menu.index', ['location' => $location])->with('status', 'Pozycja menu została usunięta.');
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

        return NavItem::where('type', 'dropdown')
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
            'url' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(NavItem::TYPES))],
            'location' => ['required', Rule::in(array_keys(NavItem::LOCATIONS))],
            'module' => ['nullable', Rule::in(array_keys(SiteSetting::MODULES))],
            'parent_id' => [
                'nullable',
                Rule::exists('nav_items', 'id')->where('type', 'dropdown')->whereNull('parent_id'),
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

        // The footer only ever renders plain links — no dropdowns/submenus.
        if ($data['location'] === 'footer') {
            $data['type'] = 'link';
            $data['parent_id'] = null;
        }

        // Dropdown/projects triggers open a panel instead of navigating, and
        // can't themselves be nested inside another dropdown. The volunteering
        // type is always a top-level link/CTA, so it can't be nested either.
        if (in_array($data['type'], ['dropdown', 'projects', 'volunteering', 'events'], true)) {
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
