<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageImage;
use Illuminate\Http\Request;

class PageImageController extends Controller
{
    public function store(Request $request, Page $page)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'max:4096'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $image = $page->images()->create([
            'alt' => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
            'order' => $data['order'] ?? $page->images()->max('order') + 1,
        ]);

        $image->addMediaFromRequest('image')->toMediaCollection('image');

        return redirect()->route('admin.podstrony.edit', $page)->with('status', 'Zdjęcie zostało dodane do galerii.');
    }

    public function update(Request $request, PageImage $image)
    {
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $image->update([
            'alt' => $data['alt'] ?? null,
            'caption' => $data['caption'] ?? null,
            'order' => $data['order'] ?? $image->order,
        ]);

        return redirect()->route('admin.podstrony.edit', $image->page)->with('status', 'Zdjęcie zostało zaktualizowane.');
    }

    public function destroy(PageImage $image)
    {
        $page = $image->page;
        $image->delete();

        return redirect()->route('admin.podstrony.edit', $page)->with('status', 'Zdjęcie zostało usunięte z galerii.');
    }
}
