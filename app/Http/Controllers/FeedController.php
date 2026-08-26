<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Kanał RSS 2.0 z aktualnościami — całość lub jedna kategoria (?kategoria=slug).
 *
 * Metody: news().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FeedController extends Controller
{
    /** Maksymalna liczba wpisów w kanale. */
    private const LIMIT = 30;

    /** Zwraca kanał RSS z najnowszymi aktualnościami (opcjonalnie z jednej kategorii). */
    public function news(Request $request)
    {
        $settings = SiteSetting::current();
        $slug = trim((string) $request->query('kategoria'));
        $category = $slug !== '' ? NewsCategory::where('slug', $slug)->first() : null;

        abort_if($slug !== '' && ! $category, 404);

        $ttl = $settings->cacheEnabled('news') ? $settings->cacheTtl('news_feed', 900) : 0;
        $key = 'news_feed:'.($category?->slug ?? 'all');

        $xml = $ttl > 0
            ? Cache::remember($key, $ttl, fn () => $this->render($settings, $category))
            : $this->render($settings, $category);

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'X-Robots-Tag' => $settings->allow_indexing ? 'index' : 'noindex',
        ]);
    }

    /** Renderuje XML kanału dla podanej (lub żadnej) kategorii. */
    private function render(SiteSetting $settings, ?NewsCategory $category): string
    {
        $items = News::published()
            ->when($category, fn ($query) => $query->where('news_category_id', $category->id))
            ->with('category')
            ->orderByDesc('published_at')
            ->limit(self::LIMIT)
            ->get();

        return view('feed.rss', [
            'settings'    => $settings,
            'category'    => $category,
            'items'       => $items,
            'title'       => $settings->site_name.' — '.($category?->name ?? 'Aktualności'),
            'description' => $category
                ? "Aktualności z kategorii „{$category->name}” w serwisie {$settings->site_name}."
                : ($settings->meta_description ?: 'Najnowsze aktualności serwisu '.$settings->site_name.'.'),
            'siteUrl'     => route('news.index', $category ? ['kategoria' => $category->slug] : []),
            'selfUrl'     => route('news.feed', $category ? ['kategoria' => $category->slug] : []),
        ])->render();
    }
}
