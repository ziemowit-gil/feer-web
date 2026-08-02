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
    /** Wyświetla listę kategorii projektów z liczbą przypisanych projektów. */
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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
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

        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
