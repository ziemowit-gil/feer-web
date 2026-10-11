<?php

namespace Modules\Blog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogCategory;

/**
 * Kategorie wpisów Wiem FEER — prosta lista z dodawaniem, edycją i usuwaniem.
 */
class BlogCategoryController extends Controller
{
    public function index()
    {
        $categories = BlogCategory::withCount('articles')->orderBy('sort_order')->orderBy('name')->get();

        return view('blog::admin.categories.index', [
            'categories' => $categories,
            'category'   => new BlogCategory,
        ]);
    }

    public function store(Request $request)
    {
        BlogCategory::create($this->validated($request));

        return redirect()->route('admin.kategorie-bloga.index')->with('status', 'Kategoria została dodana.');
    }

    public function edit(BlogCategory $category)
    {
        return view('blog::admin.categories.edit', compact('category'));
    }

    public function update(Request $request, BlogCategory $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.kategorie-bloga.index')->with('status', 'Kategoria została zaktualizowana.');
    }

    public function destroy(BlogCategory $category)
    {
        // Brak klucza obcego (SQLite) — wpisy tej kategorii zostają bez kategorii.
        BlogArticle::where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        return redirect()->route('admin.kategorie-bloga.index')->with('status', 'Kategoria została usunięta. Jej wpisy zostały bez kategorii.');
    }

    private function validated(Request $request, ?BlogCategory $category = null): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'slug'        => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], ['name' => 'nazwa', 'slug' => 'adres', 'description' => 'opis', 'sort_order' => 'kolejność']);

        $slug = Str::slug(trim((string) ($data['slug'] ?? '')) ?: $data['name']) ?: 'kategoria';

        $request->merge(['_slug' => $slug]);
        $request->validate([
            '_slug' => [Rule::unique('blog.blog_categories', 'slug')->ignore($category?->id)],
        ], ['_slug.unique' => 'Kategoria o takim adresie już istnieje.']);

        return [
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
