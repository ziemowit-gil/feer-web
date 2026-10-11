<?php

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Models\SiteSetting;
use App\Support\TableOfContents;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogTag;

/**
 * Publiczna część bloga Wiem FEER: lista wpisów (wyszukiwanie, filtr kategorii
 * i tagu, paginacja, wpis wyróżniony), strona wpisu (spis treści, autor,
 * powiązane, poprzedni/następny, newsletter) i kanał RSS.
 *
 * Metody: index(), author(), authors(), legacyRedirect(), show(), feed().
 */
class BlogController extends Controller
{
    /** Wpisów na stronę listy. */
    public const PER_PAGE = 12;

    /** Tagów w kolumnie bocznej listy. */
    private const SIDEBAR_TAGS = 15;

    /** Wpisów w kanale RSS. */
    private const FEED_LIMIT = 30;

    public function index(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);
        $categorySlug = trim((string) $request->query('kategoria'));
        $tagSlug = trim((string) $request->query('tag'));
        $authorSlug = trim((string) $request->query('autor'));

        $category = $categorySlug !== '' ? BlogCategory::where('slug', $categorySlug)->firstOrFail() : null;
        $tag = $tagSlug !== '' ? BlogTag::where('slug', $tagSlug)->firstOrFail() : null;
        $author = $authorSlug !== '' ? BlogAuthor::where('slug', $authorSlug)->firstOrFail() : null;
        $filtered = $q !== '' || $category || $tag || $author;

        // Wpis wyróżniony tylko na liście bez filtrów: oznaczony w panelu
        // „Wyróżniony", a gdy takiego nie ma — najnowszy.
        $featured = null;
        if (! $filtered) {
            $featured = $this->listQuery()->where('is_featured', true)->latestFirst()->first()
                ?? $this->listQuery()->latestFirst()->first();
        }

        $articles = $this->listQuery()
            ->when($featured, fn (Builder $query) => $query->whereKeyNot($featured->getKey()))
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id))
            ->when($tag, fn (Builder $query) => $query->whereHas('tags', fn (Builder $t) => $t->whereKey($tag->id)))
            ->when($author, fn (Builder $query) => $query->byAuthor($author))
            ->when($q !== '', function (Builder $query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(fn (Builder $w) => $w
                    ->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('author_name', 'like', $like)
                    ->orWhereHas('author', fn (Builder $a) => $a->where('name', 'like', $like)));
            })
            ->latestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('blog::index', array_merge(
            compact('articles', 'featured', 'category', 'tag', 'author', 'q', 'filtered'),
            $this->sidebar(),
        ));
    }

    /** Strona autora: profil i jego wpisy (jako autora głównego lub współautora). */
    public function author(BlogAuthor $author)
    {
        $articles = $this->listQuery()
            ->byAuthor($author)
            ->latestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        abort_if(! $author->is_active && $articles->total() === 0, 404);

        return view('blog::author', array_merge(compact('author', 'articles'), $this->sidebar()));
    }

    /** Lista autorów z co najmniej jednym opublikowanym wpisem. */
    public function authors()
    {
        $authors = BlogAuthor::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (BlogAuthor $a) => BlogArticle::listed()->byAuthor($a)->exists())
            ->values();

        return view('blog::authors', array_merge(compact('authors'), $this->sidebar()));
    }

    /** Dawne adresy (/wiem-feer/…, /blog/…) → /wiem/… na stałe, z parametrami zapytania. */
    public function legacyRedirect(Request $request, ?string $path = null)
    {
        $target = '/' . trim((string) config('blog.prefix', 'wiem'), '/') . ($path ? '/' . ltrim($path, '/') : '');
        $query = $request->getQueryString();

        return redirect()->to(url($target) . ($query ? '?' . $query : ''), 301);
    }

    public function show(Request $request, BlogArticle $article)
    {
        $preview = $this->isPreviewRequest($request);
        abort_unless($article->isVisible() || $preview, 404);

        $article->load(['approvedComments.replies', 'category', 'tags', 'author', 'coAuthors']);

        [$body, $toc] = TableOfContents::inject($article->body);

        return view('blog::show', array_merge([
            'article'  => $article,
            'preview'  => $preview,
            'body'     => $body,
            'toc'      => $toc,
            'related'  => $this->related($article),
            'previous' => $this->neighbour($article, 'previous'),
            'next'     => $this->neighbour($article, 'next'),
        ], $this->sidebar()));
    }

    /** Zapytanie bazowe list: widoczne wpisy z danymi do kart. */
    private function listQuery(): Builder
    {
        return BlogArticle::listed()->with(['category', 'author', 'coAuthors']);
    }

    /**
     * Dane kolumny bocznej: kategorie z wpisami, najczęstsze tagi, panel newslettera.
     *
     * @return array{categories: Collection, tags: Collection, newsletter: ?array}
     */
    private function sidebar(): array
    {
        return [
            'categories' => BlogCategory::query()
                ->whereHas('articles', fn (Builder $query) => $query->listed())
                ->orderBy('sort_order')->orderBy('name')
                ->get(),
            'tags' => BlogTag::query()
                ->withCount(['articles' => fn (Builder $query) => $query->listed()])
                ->get()
                ->filter(fn (BlogTag $t) => $t->articles_count > 0)
                ->sortBy([['articles_count', 'desc'], ['name', 'asc']])
                ->take(self::SIDEBAR_TAGS)
                ->values(),
            'newsletter' => PageController::sectionNewsletterDefaults(),
        ];
    }

    /** Kanał RSS 2.0 z najnowszymi wpisami Wiem FEER. */
    public function feed()
    {
        $settings = SiteSetting::current();
        $items = BlogArticle::listed()->with(['category', 'author', 'coAuthors'])->latestFirst()->limit(self::FEED_LIMIT)->get();

        $xml = view('blog::feed', [
            'settings' => $settings,
            'items'    => $items,
            'title'    => request()->attributes->get('blog_site') ? $settings->site_name : 'Wiem FEER — ' . $settings->site_name,
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'X-Robots-Tag' => $settings->allow_indexing ? 'index' : 'noindex',
        ]);
    }

    /**
     * Do 3 powiązanych wpisów: najpierw z tej samej kategorii lub o wspólnych
     * tagach (więcej wspólnych tagów = wyżej), dopełnione najnowszymi.
     */
    private function related(BlogArticle $article): Collection
    {
        $tagIds = $article->tags->pluck('id');

        $candidates = BlogArticle::listed()
            ->whereKeyNot($article->getKey())
            ->where(function (Builder $query) use ($article, $tagIds) {
                $query->when($article->category_id, fn (Builder $q) => $q->orWhere('category_id', $article->category_id))
                    ->when($tagIds->isNotEmpty(), fn (Builder $q) => $q->orWhereHas('tags', fn (Builder $t) => $t->whereIn('blog_tags.id', $tagIds)));
            })
            ->when(! $article->category_id && $tagIds->isEmpty(), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->with('tags')
            ->latestFirst()
            ->limit(30)
            ->get()
            ->sortByDesc(fn (BlogArticle $a) => ($a->category_id && $a->category_id === $article->category_id ? 1 : 0)
                + $a->tags->pluck('id')->intersect($tagIds)->count())
            ->take(3)
            ->values();

        if ($candidates->count() < 3) {
            $candidates = $candidates->concat(
                BlogArticle::listed()
                    ->whereKeyNot($article->getKey())
                    ->whereNotIn('id', $candidates->pluck('id'))
                    ->latestFirst()
                    ->limit(3 - $candidates->count())
                    ->get()
            );
        }

        return $candidates;
    }

    /** Poprzedni (starszy) albo następny (nowszy) wpis według daty publikacji. */
    private function neighbour(BlogArticle $article, string $direction): ?BlogArticle
    {
        $date = $article->published_at;
        if (! $date) {
            return null;
        }

        $older = $direction === 'previous';

        return BlogArticle::listed()
            ->whereKeyNot($article->getKey())
            ->where(fn (Builder $query) => $query
                ->where('published_at', $older ? '<' : '>', $date)
                ->orWhere(fn (Builder $same) => $same->where('published_at', $date)->where('id', $older ? '<' : '>', $article->id)))
            ->orderBy('published_at', $older ? 'desc' : 'asc')
            ->orderBy('id', $older ? 'desc' : 'asc')
            ->first();
    }
}
