<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: CRUD kategorii projektów.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CategoryController extends Controller
{
    /** Wyświetla listę kategorii działań z liczbą przypisanych projektów. */
    public function index()
    {
        $categories = Category::withCount('projects')->orderBy('order')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    /** Wyświetla formularz tworzenia nowej kategorii. */
    public function create()
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    /** Zapisuje nową kategorię z wygenerowanym unikalnym slugiem. */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

        Category::create($data);

        return redirect()->route('admin.kategorie.index')->with('status', 'Kategoria została utworzona.');
    }

    /** Wyświetla formularz edycji kategorii. */
    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    /** Aktualizuje kategorię projektów. */
    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name'], $category->id);

        $category->update($data);

        return redirect()->route('admin.kategorie.index')->with('status', 'Kategoria została zaktualizowana.');
    }

    /** Usuwa kategorię projektów. */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.kategorie.index')->with('status', 'Kategoria została usunięta.');
    }

    /**
     * Edytowalne elementy portalu (komunikat, „Na skróty”, kontakt do zespołu portalu, „Pozostańmy w kontakcie”,
     * nagłówki widżetów). Puste pola nie są zapisywane — portal pokazuje wtedy domyślną treść albo nic.
     */
    private function portalOptions(Request $request): array
    {
        $titleRules = collect(Category::PORTAL_WIDGET_TITLES)->keys()
            ->mapWithKeys(fn ($k) => ["portal_options.titles.$k" => ['nullable', 'string', 'max:120']])->all()
            + collect(Category::PORTAL_LABELS)->keys()
                ->mapWithKeys(fn ($k) => ["portal_options.labels.$k" => ['nullable', 'string', 'max:200']])->all();
        $v = $request->validate([
            'portal_options' => ['array'],
            'portal_options.notice' => ['nullable', 'string', 'max:500'],
            'portal_options.notice_link_label' => ['nullable', 'string', 'max:80'],
            'portal_options.notice_link_url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'portal_options.shortcuts_json' => ['nullable', 'json', 'max:10000'],
            'portal_options.contact_text' => ['nullable', 'string', 'max:1000'],
            'portal_options.contact_email' => ['nullable', 'email', 'max:255'],
            'portal_options.contact_phone' => ['nullable', 'string', 'max:40'],
            'portal_options.stay_enabled' => ['nullable', 'boolean'],
            'portal_options.stay_title' => ['nullable', 'string', 'max:120'],
            'portal_options.stay_text' => ['nullable', 'string', 'max:500'],
            'portal_options.nav_title' => ['nullable', 'string', 'max:120'],
            'portal_options.shortcuts_title' => ['nullable', 'string', 'max:120'],
            'portal_options.shortcuts_enabled' => ['nullable', 'boolean'],
            'portal_options.nav_json' => ['nullable', 'json', 'max:20000'],
            'portal_options.layout' => ['nullable', 'array'],
            'portal_options.layout.sidebar' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Category::PORTAL_LAYOUT['sidebar']))],
            'portal_options.layout.slider' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Category::PORTAL_LAYOUT['slider']))],
            'portal_options.layout.header' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Category::PORTAL_LAYOUT['header']))],
            'portal_options.layout.shortcuts' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Category::PORTAL_LAYOUT['shortcuts']))],
            'portal_options.layout.widths' => ['nullable', 'array'],
            'portal_options.layout.widths.*' => [\Illuminate\Validation\Rule::in(['full', 'half'])],
        ] + $titleRules, [
            'portal_options.notice_link_url.regex' => 'Adres linku w komunikacie musi zaczynać się od https://, http:// albo / (strona w serwisie).',
            'portal_options.contact_email.email' => 'Podaj poprawny adres e-mail do kontaktu.',
        ])['portal_options'] ?? [];

        // „Na skróty” (szybkie akcje) z edytora listy: nazwa, adres, ikona.
        $shortcuts = [];
        foreach ((array) (json_decode((string) ($v['shortcuts_json'] ?? ''), true) ?: []) as $n => $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($label === '' && $url === '') {
                continue; // pusty, niewypełniony wiersz
            }
            if ($label === '' || ! preg_match('#^(https?://|/)\S*$#i', $url)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'portal_options.shortcuts_json' => 'Na skróty, pozycja '.($n + 1).': wpisz nazwę i adres zaczynający się od https://, http:// albo /.',
                ]);
            }
            $icon = (string) ($row['icon'] ?? '');
            $shortcuts[] = array_filter(['label' => Str::limit($label, 120, ''), 'url' => Str::limit($url, 500, ''), 'icon' => isset(Category::PORTAL_SHORTCUT_ICONS[$icon]) ? $icon : '']);
        }

        return array_filter([
            'notice' => trim((string) ($v['notice'] ?? '')),
            'notice_link_label' => trim((string) ($v['notice_link_label'] ?? '')),
            'notice_link_url' => trim((string) ($v['notice_link_url'] ?? '')),
            'shortcuts' => array_slice($shortcuts, 0, 12),
            'contact_text' => trim((string) ($v['contact_text'] ?? '')),
            'contact_email' => trim((string) ($v['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($v['contact_phone'] ?? '')),
            'stay_enabled' => $request->boolean('portal_options.stay_enabled', true),
            'stay_title' => trim((string) ($v['stay_title'] ?? '')),
            'stay_text' => trim((string) ($v['stay_text'] ?? '')),
            'titles' => array_filter(array_map(fn ($t) => trim((string) $t), $v['titles'] ?? []), fn ($t) => $t !== ''),
            'labels' => array_filter(array_map(fn ($t) => trim((string) $t), array_intersect_key($v['labels'] ?? [], Category::PORTAL_LABELS)), fn ($t) => $t !== ''),
            'shortcuts_enabled' => $request->boolean('portal_options.shortcuts_enabled', true),
            'shortcuts_title' => trim((string) ($v['shortcuts_title'] ?? '')),
            'nav' => array_filter([
                'title' => trim((string) ($v['nav_title'] ?? '')),
                'items' => $this->portalNavItems((string) ($v['nav_json'] ?? '')),
            ]),
            'layout' => array_filter([
                ...array_intersect_key($v['layout'] ?? [], Category::PORTAL_LAYOUT),
                // Zapisujemy tylko widżety na pół szerokości — pełna jest domyślna.
                'widths' => array_filter(array_intersect_key($v['layout']['widths'] ?? [], Category::PORTAL_WIDGETS), fn ($w) => $w === 'half'),
            ]),
        ], fn ($x) => $x !== '' && $x !== []);
    }

    /**
     * Menu portalu z edytora w panelu (JSON): działania [id, hidden, label, subs] i własne linki [label, url]
     * w ustalonej kolejności. Nieprawidłowe pozycje odrzucamy z komunikatem, nieznane pola pomijamy.
     */
    private function portalNavItems(string $json): array
    {
        $items = [];
        foreach ((array) (json_decode($json, true) ?: []) as $n => $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') === 'link') {
                $label = trim((string) ($item['label'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));
                if ($label === '' && $url === '') {
                    continue; // pusty, niewypełniony wiersz
                }
                if ($label === '' || ! preg_match('#^(https?://|/)\S*$#i', $url)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'portal_options.nav_json' => 'Menu portalu, pozycja '.($n + 1).': własny link potrzebuje nazwy i adresu zaczynającego się od https://, http:// albo /.',
                    ]);
                }
                $items[] = ['type' => 'link', 'label' => Str::limit($label, 120, ''), 'url' => Str::limit($url, 500, '')];
            } elseif (($item['type'] ?? '') === 'project' && (int) ($item['id'] ?? 0) > 0) {
                $items[] = array_filter([
                    'type' => 'project',
                    'id' => (int) $item['id'],
                    'hidden' => ! empty($item['hidden']),
                    'label' => Str::limit(trim((string) ($item['label'] ?? '')), 120, ''),
                    'subs' => isset(Category::PORTAL_NAV_SUBS[$item['subs'] ?? '']) && $item['subs'] !== 'collapsed' ? $item['subs'] : '',
                ], fn ($x) => $x !== '' && $x !== false);
            }
            if (count($items) >= 60) {
                break;
            }
        }

        return $items;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9 _-]+$/i'],
        ]);
        if (! \Illuminate\Support\Facades\Schema::hasColumn('categories', 'icon')) {
            unset($data['icon']); // do czasu migracji
        }

        // Portal kategorii (/portal/{adres}, subdomena {adres}.feer.org.pl) — po migracji.
        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_slug')) {
            $category = $request->route('category');
            $portal = $request->validate([
                'portal_slug' => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                    \Illuminate\Validation\Rule::notIn(Category::RESERVED_PORTAL_SLUGS),
                    \Illuminate\Validation\Rule::unique('categories', 'portal_slug')->ignore($category?->id)],
                'portal_title' => ['nullable', 'string', 'max:255'],
                'portal_intro' => ['nullable', 'string', 'max:5000'],
                'portal_slider_id' => ['nullable', 'integer'],
                'portal_rss_url' => ['nullable', 'url:http,https', 'max:500'],
                'portal_widgets' => ['nullable', 'array'],
                'portal_widgets.*' => [\Illuminate\Validation\Rule::in(array_keys(Category::PORTAL_WIDGETS))],
                'portal_widgets_order' => ['nullable', 'string', 'max:200'],
            ], [
                'portal_slug.regex' => 'Adres portalu: same małe litery bez polskich znaków, cyfry i myślniki (np. szkolenia).',
                'portal_slug.not_in' => 'Ten adres jest zarezerwowany dla innej usługi — wybierz inny.',
                'portal_slug.unique' => 'Ten adres ma już portal innej kategorii.',
            ]);
            $portal['portal_slug'] = ($portal['portal_slug'] ?? null) ?: null;
            // Kolejność widżetów z ukrytego pola (przyciski „w górę / w dół”), zaznaczone pola decydują o włączeniu.
            $order = array_values(array_filter(explode(',', (string) ($portal['portal_widgets_order'] ?? '')), fn ($k) => isset(Category::PORTAL_WIDGETS[$k])));
            $enabled = $portal['portal_widgets'] ?? [];
            $portal['portal_widgets'] = $request->has('portal_widgets_order')
                ? array_values(array_filter(array_unique([...$order, ...array_keys(Category::PORTAL_WIDGETS)]), fn ($k) => in_array($k, $enabled, true)))
                : null;
            unset($portal['portal_widgets_order']);
            $data += $portal;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_options') && $request->has('portal_options')) {
            $data['portal_options'] = $this->portalOptions($request);
        }

        $data['slug'] = trim($data['slug'] ?? '');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'kategoria';
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
