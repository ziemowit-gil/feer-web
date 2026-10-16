<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Newsletter\Services\ContentFeeder;

/** JSON z treścią CMS do edytora (podgląd bloku „Najnowsze aktualności", wstawianie aktualności). */
class ContentController extends Controller
{
    public function items(Request $request, string $source, ContentFeeder $feeder)
    {
        if ($source === 'banners') {
            return $this->banners($request);
        }
        abort_unless(array_key_exists($source, ContentFeeder::SOURCES), 404);

        $opts = [
            'limit'    => (int) $request->query('limit', 5),
            'category' => $request->query('category'),
            'since'    => $request->query('since', 'all'),
            'sort'     => $request->query('sort', 'newest'),
            'max'      => 30,
        ];
        if ($request->filled('q')) {
            $opts['limit'] = 30; // wyszukiwanie filtruje po pobraniu
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

    /** Banery graficzne z modułu Bannery do wstawienia jako blok „Obraz” (aktywne, niewygasłe). */
    private function banners(Request $request)
    {
        if (! Schema::hasTable('banners')) {
            return response()->json(['items' => [], 'html' => '', 'categories' => []]);
        }
        $q = mb_strtolower(trim((string) $request->query('q', '')));

        $items = Banner::query()
            ->where('type', 'image')->whereNotNull('image_path')->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->latest('id')->limit(50)->get()
            ->filter(fn (Banner $b) => $q === '' || str_contains(mb_strtolower((string) $b->name), $q))
            ->values()
            ->map(fn (Banner $b) => [
                'id' => $b->id,
                'title' => $b->name,
                'excerpt' => '',
                'url' => (string) $b->link_url,
                'image' => url(Storage::url($b->image_path)),
                'image_alt' => (string) ($b->image_alt ?: $b->name),
                'date' => $b->starts_at || $b->ends_at
                    ? trim(($b->starts_at?->format('d.m.Y') ?? '') . ' – ' . ($b->ends_at?->format('d.m.Y') ?? ''), ' –')
                    : null,
                'category' => ($b->width && $b->height ? "{$b->width} × {$b->height} px · " : '') . $b->statusLabel(),
                'banner' => true,
            ]);

        return response()->json(['items' => $items, 'html' => '', 'categories' => []]);
    }
}
