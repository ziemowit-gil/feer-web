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
        $newsCategories = NewsCategory::withCount(['news', 'news as published_count' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('order')->orderBy('name')->get();

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

    /**
     * Usuwa kategorię aktualności. Gdy ma przypisane newsy, trzeba wskazać, dokąd je przenieść
     * (`move_to` = id innej kategorii albo „none" = bez kategorii) — newsy nigdy nie znikają razem z kategorią.
     */
    public function destroy(Request $request, NewsCategory $newsCategory)
    {
        $count = $newsCategory->news()->count();

        if ($count > 0) {
            $target = $request->input('move_to');
            if ($target === null || $target === '') {
                return redirect()->route('admin.kategorie-newsow.index')
                    ->with('error', "Kategoria „{$newsCategory->name}” ma {$count} newsów — wybierz, dokąd je przenieść, zanim ją usuniesz.");
            }
            $newId = $target === 'none' ? null : NewsCategory::whereKey((int) $target)->where('id', '!=', $newsCategory->id)->value('id');
            if ($target !== 'none' && $newId === null) {
                return redirect()->route('admin.kategorie-newsow.index')->with('error', 'Wybrana kategoria docelowa nie istnieje.');
            }
            $newsCategory->news()->update(['news_category_id' => $newId]);
        }

        $newsCategory->delete();

        return redirect()->route('admin.kategorie-newsow.index')->with('status', 'Kategoria newsów została usunięta'.($count ? " (przeniesiono newsy: {$count})." : '.'));
    }

    /** Przesuwa kategorię o jedno miejsce w górę lub w dół (kolejność na stronie i w listach wyboru). */
    public function move(Request $request, NewsCategory $newsCategory)
    {
        $direction = $request->input('direction') === 'up' ? -1 : 1;
        $list = NewsCategory::orderBy('order')->orderBy('name')->get()->values();
        $i = $list->search(fn ($c) => $c->id === $newsCategory->id);
        $j = $i + $direction;

        if ($i !== false && $j >= 0 && $j < $list->count()) {
            $moved = $list->splice($i, 1);
            $list->splice($j, 0, $moved->all());
            $list->values()->each(fn ($c, $n) => $c->update(['order' => $n + 1]));
        }

        return redirect()->route('admin.kategorie-newsow.index');
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
