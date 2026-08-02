<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Panel admin: lista tagów z licznikiem użycia i możliwością zmiany nazwy / usunięcia.
 *
 * Metody: index(), update(), destroy().
 */
class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::withCount('news')->orderByDesc('news_count')->orderBy('name')->get();

        return view('admin.tags.index', compact('tags'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $tag->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return redirect()->route('admin.tagi.index')->with('status', 'Tag został zaktualizowany.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->news()->detach();
        $tag->delete();

        return redirect()->route('admin.tagi.index')->with('status', 'Tag został usunięty.');
    }
}
