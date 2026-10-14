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
     * Edytowalne elementy portalu (komunikat, „Na skróty”, kontakt do obszaru, „Pozostańmy w kontakcie”,
     * nagłówki widżetów). Puste pola nie są zapisywane — portal pokazuje wtedy domyślną treść albo nic.
     */
    private function portalOptions(Request $request): array
    {
        $titleRules = collect(Category::PORTAL_WIDGET_TITLES)->keys()
            ->mapWithKeys(fn ($k) => ["portal_options.titles.$k" => ['nullable', 'string', 'max:120']])->all();
        $v = $request->validate([
            'portal_options' => ['array'],
            'portal_options.notice' => ['nullable', 'string', 'max:500'],
            'portal_options.notice_link_label' => ['nullable', 'string', 'max:80'],
            'portal_options.notice_link_url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'portal_options.shortcuts' => ['nullable', 'string', 'max:3000'],
            'portal_options.contact_text' => ['nullable', 'string', 'max:1000'],
            'portal_options.contact_email' => ['nullable', 'email', 'max:255'],
            'portal_options.contact_phone' => ['nullable', 'string', 'max:40'],
            'portal_options.stay_enabled' => ['nullable', 'boolean'],
            'portal_options.stay_title' => ['nullable', 'string', 'max:120'],
            'portal_options.stay_text' => ['nullable', 'string', 'max:500'],
        ] + $titleRules, [
            'portal_options.notice_link_url.regex' => 'Adres linku w komunikacie musi zaczynać się od https://, http:// albo / (strona w serwisie).',
            'portal_options.contact_email.email' => 'Podaj poprawny adres e-mail do kontaktu.',
        ])['portal_options'] ?? [];

        // „Na skróty”: jeden wiersz = „Etykieta | adres”.
        $shortcuts = [];
        foreach (preg_split('/\R/', (string) ($v['shortcuts'] ?? '')) as $n => $line) {
            if (trim($line) === '') {
                continue;
            }
            [$label, $url] = array_map('trim', explode('|', $line, 2)) + [1 => ''];
            if ($label === '' || ! preg_match('#^(https?://|/)\S*$#i', $url)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'portal_options.shortcuts' => 'Na skróty, wiersz '.($n + 1).': wpisz „Etykieta | adres”, a adres zacznij od https://, http:// albo /.',
                ]);
            }
            $shortcuts[] = ['label' => Str::limit($label, 120, ''), 'url' => $url];
        }

        return array_filter([
            'notice' => trim((string) ($v['notice'] ?? '')),
            'notice_link_label' => trim((string) ($v['notice_link_label'] ?? '')),
            'notice_link_url' => trim((string) ($v['notice_link_url'] ?? '')),
            'shortcuts' => array_slice($shortcuts, 0, 12),
            'contact_text' => trim((string) ($v['contact_text'] ?? '')),
            'contact_email' => trim((string) ($v['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($v['contact_phone'] ?? '')),
            'stay_enabled' => $request->boolean('portal_options.stay_enabled'),
            'stay_title' => trim((string) ($v['stay_title'] ?? '')),
            'stay_text' => trim((string) ($v['stay_text'] ?? '')),
            'titles' => array_filter(array_map(fn ($t) => trim((string) $t), $v['titles'] ?? []), fn ($t) => $t !== ''),
        ], fn ($x) => $x !== '' && $x !== []);
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
