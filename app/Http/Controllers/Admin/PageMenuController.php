<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavItem;
use App\Models\Page;
use App\Support\PageMenuPositions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * „Strony i nawigacja”: menu nawigacji ustawiane z panelu strony w drzewie stron (Strony → wybrana strona → „W menu
 * nawigacji”). Dodaje stronę do menu głównego albo stopki (jako zwykły link do adresu strony), zmienia napis
 * i widoczność pozycji oraz usuwa ją z menu. Linki zewnętrzne, nagłówki, menu rozwijane i mega-menu zostają
 * w Menu nawigacji. Te same uprawnienia co Menu nawigacji (administrator).
 */
class PageMenuController extends Controller
{
    public function store(Request $request, Page $page): RedirectResponse
    {
        $this->ensureSameSite($page);
        $data = $request->validate([
            'location' => ['required', Rule::in(['main', 'footer'])],
            'parent_id' => ['nullable', 'integer'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $parentId = null;
        if ($data['location'] === 'main' && ! empty($data['parent_id'])) {
            $parent = NavItem::forCurrentSite()->where('location', 'main')->whereNull('parent_id')->find((int) $data['parent_id']);
            if (! $parent) {
                return $this->back($page)->withErrors(['parent_id' => 'Wybrana pozycja nadrzędna nie istnieje w menu głównym.']);
            }
            $parentId = $parent->id;
        }

        $path = rtrim((string) parse_url($page->publicUrl(), PHP_URL_PATH), '/') ?: '/';
        $order = (int) NavItem::forCurrentSite()->where('location', $data['location'])->where('parent_id', $parentId)->max('order');

        NavItem::create([
            'label' => trim((string) ($data['label'] ?? '')) ?: $page->title,
            'url' => $path, 'type' => 'link', 'location' => $data['location'],
            'parent_id' => $parentId, 'is_active' => true, 'order' => $order + 1,
        ]);

        return $this->back($page)->with('status', 'Dodano stronę do: '.PageMenuPositions::locationLabel($data['location']).'.');
    }

    public function update(Request $request, Page $page, NavItem $navItem): RedirectResponse
    {
        $this->ensureSameSite($page);
        $this->ensurePointsToPage($page, $navItem);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ], ['label.required' => 'Napis w menu nie może być pusty.']);

        $navItem->update(['label' => trim($data['label']), 'is_active' => $request->boolean('is_active')]);

        return $this->back($page)->with('status', 'Zapisano pozycję menu „'.$navItem->label.'”.');
    }

    public function destroy(Page $page, NavItem $navItem): RedirectResponse
    {
        $this->ensureSameSite($page);
        $this->ensurePointsToPage($page, $navItem);
        if ($navItem->children()->exists()) {
            return $this->back($page)->with('error', 'Pozycja „'.$navItem->label.'” ma podpozycje — usuń je albo przenieś w Menu nawigacji.');
        }

        $label = $navItem->label;
        $navItem->delete();

        return $this->back($page)->with('status', "Usunięto „{$label}” z menu. Strona nadal istnieje.");
    }

    /** Wyłącza dawne „Pokaż w menu głównym” (strona dopisywana do menu automatycznie). */
    public function disableAuto(Page $page): RedirectResponse
    {
        $this->ensureSameSite($page);
        $page->forceFill(['show_in_menu' => false])->save();

        return $this->back($page)->with('status', 'Strona nie jest już dopisywana do menu automatycznie.');
    }

    private function back(Page $page): RedirectResponse
    {
        return redirect()->to(route('admin.podstrony.index', ['wybrana' => $page->id]).'#menu-nawigacji');
    }

    private function ensureSameSite(Page $page): void
    {
        abort_unless(Page::forCurrentSite()->whereKey($page->id)->exists(), 404);
    }

    /** Zmieniać i usuwać można tylko pozycje tej witryny, które prowadzą do tej strony. */
    private function ensurePointsToPage(Page $page, NavItem $navItem): void
    {
        abort_unless(NavItem::forCurrentSite()->whereKey($navItem->id)->exists(), 404);
        $positions = PageMenuPositions::forPages(collect([$page]))[$page->id] ?? [];
        abort_unless(collect($positions)->contains(fn ($p) => $p['item']?->id === $navItem->id), 404);
    }
}
