<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Newsletter\Services\ContentFeeder;

/** JSON z treścią CMS do edytora (podgląd bloku „Najnowsze aktualności", wstawianie aktualności). */
class ContentController extends Controller
{
    public function items(Request $request, string $source, ContentFeeder $feeder)
    {
        abort_unless(array_key_exists($source, ContentFeeder::SOURCES), 404);

        $opts = [
            'limit'    => (int) $request->query('limit', 5),
            'category' => $request->query('category'),
            'since'    => $request->query('since', 'all'),
            'sort'     => $request->query('sort', 'newest'),
        ];
        if ($request->filled('q')) {
            $opts['limit'] = 12;
        }
        $items = $feeder->items($source, $opts);
        if ($request->filled('q')) {
            $q = mb_strtolower((string) $request->query('q'));
            $items = $items->filter(fn ($i) => str_contains(mb_strtolower($i->title), $q))->values();
        }

        return response()->json([
            'items' => $items->map(fn ($i) => [
                'id' => $i->id, 'title' => $i->title, 'excerpt' => $i->excerpt, 'url' => $i->url, 'image' => $i->imageUrl,
                'image_alt' => $i->imageAlt, 'date' => $i->publishedAt?->format('d.m.Y'), 'category' => $i->category, 'color' => $i->categoryColor,
            ]),
            'html' => $feeder->renderItems($items, (string) $request->query('layout', 'list'), $opts['sort'] === 'category'),
            'categories' => $source === 'news' ? $feeder->newsCategories() : [],
        ]);
    }
}
