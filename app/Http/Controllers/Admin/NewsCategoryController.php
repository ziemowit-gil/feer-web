<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: CRUD kategorii aktualności.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class NewsCategoryController extends Controller
{
    /** Wyświetla listę kategorii aktualności z liczbą przypisanych newsów. */
    public function index()
    {
        $newsCategories = NewsCategory::withCount('news')->orderBy('order')->orderBy('name')->get();

        return view('admin.news-categories.index', compact('newsCategories'));
    }

    /** Wyświetla formularz tworzenia nowej kategorii aktualności. */
    public function create()
    {
        return view('admin.news-categories.form', ['newsCategory' => new NewsCategory]);
    }

    /** Zapisuje nową kategorię aktualności z wygenerowanym unikalnym slugiem. */
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

        NewsCategory::create($data);

        return redirect()->route('admin.kategorie-newsow.index')->with('status', 'Kategoria newsów została utworzona.');
    }

    /** Wyświetla formularz edycji kategorii aktualności. */
    public function edit(NewsCategory $newsCategory)
    {
        return view('admin.news-categories.form', compact('newsCategory'));
    }

    /** Aktualizuje kategorię aktualności. */
    public function update(Request $request, NewsCategory $newsCategory)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name'], $newsCategory->id);

        $newsCategory->update($data);

        return redirect()->route('admin.kategorie-newsow.index')->with('status', 'Kategoria newsów została zaktualizowana.');
    }

    /** Usuwa kategorię aktualności. */
    public function destroy(NewsCategory $newsCategory)
    {
        $newsCategory->delete();

        return redirect()->route('admin.kategorie-newsow.index')->with('status', 'Kategoria newsów została usunięta.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'kategoria';
        $slug = $base;
        $suffix = 2;

        while (NewsCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
