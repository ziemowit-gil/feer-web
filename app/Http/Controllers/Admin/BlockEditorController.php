<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\BlockRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Edytor blokowy stron (w stylu Gutenberga) — pełnoekranowa aplikacja na
 * stylach frontu, więc redakcja widzi treść tak, jak zobaczy ją odwiedzający.
 *
 * Bloki (JSON) zapisujemy w `pages.blocks`, a wyrenderowany z nich HTML w
 * `pages.content` — reszta serwisu (szablony, wyszukiwarka, spis treści,
 * shortcode'y, historia wersji) korzysta z `content` jak dotąd.
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlockEditorController extends Controller
{
    public function edit(Page $page)
    {
        abort_if($page->is_locked && ! auth()->user()->isAdmin(), 403, 'Strona została zablokowana do edycji przez administratora.');

        // Strona bez bloków (np. edytowana dotąd klasycznie) startuje z jednym blokiem HTML.
        $blocks = $page->blocks;
        $imported = false;
        if (! is_array($blocks)) {
            $blocks = filled($page->content) ? [['type' => 'html', 'attrs' => ['html' => (string) $page->content]]] : [];
            $imported = filled($page->content);
        }

        return view('admin.pages.block-editor', [
            'page' => $page,
            'editorConfig' => [
                'page' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'meta_description' => (string) $page->meta_description,
                    'type' => Page::TYPES[$page->type] ?? $page->type,
                    'is_published' => (bool) $page->is_published,
                ],
                'blocks' => BlockRenderer::normalize($blocks),
                'imported' => $imported,
                'urls' => [
                    'save' => route('admin.podstrony.bloki.update', $page),
                    'view' => $page->publicUrl(),
                    'admin' => route('admin.podstrony.edit', $page),
                    'images' => route('admin.multimedia.images'),
                    'upload' => route('admin.multimedia.upload-ajax'),
                ],
            ],
        ]);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        abort_if($page->is_locked && ! auth()->user()->isAdmin(), 403, 'Strona została zablokowana do edycji przez administratora.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'blocks' => ['present', 'array', 'max:'.BlockRenderer::MAX_BLOCKS],
        ]);

        $blocks = BlockRenderer::normalize($data['blocks']);

        $page->update([
            'title' => $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'blocks' => $blocks,
            'content' => BlockRenderer::render($blocks),
        ]);

        return response()->json([
            'ok' => true,
            'blocks' => $blocks,
            'saved_at' => now()->format('H:i'),
        ]);
    }
}
