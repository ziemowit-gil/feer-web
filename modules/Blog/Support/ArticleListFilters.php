<?php

namespace Modules\Blog\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogCategory;
use Modules\Blog\Models\BlogTag;

/**
 * Wyszukiwanie, filtry i sortowanie listy artykułów Wiem FEER w panelu.
 *
 * Wszystko z parametrów GET (adres do udostępnienia, działa bez JS):
 * q, status, kategoria (id/slug/„brak"), autor (id — także współautor), tag (slug/id),
 * format (html/markdown), od/do (data publikacji), bez_zdjecia, bez_seo, do_moderacji,
 * sort/dir, na_strone. Nieprawidłowe wartości są ignorowane.
 */
final class ArticleListFilters
{
    /** Statusy w filtrze: jak BlogArticle::STATUSES + „W przygotowaniu". */
    public const STATUSES = BlogArticle::STATUSES + ['wip' => 'W przygotowaniu'];

    public const FORMATS = ['html' => 'Edytor wizualny (HTML)', 'markdown' => 'Markdown'];

    /** Sortowalne kolumny: parametr → etykieta. */
    public const SORTS = [
        'tytul'      => 'Tytuł',
        'autor'      => 'Autor',
        'kategoria'  => 'Kategoria',
        'publikacja' => 'Data publikacji',
        'zmiana'     => 'Ostatnia zmiana',
        'komentarze' => 'Komentarze',
        'wyswietlenia' => 'Wyświetlenia',
    ];

    /** Kolumny, które przy pierwszym kliknięciu sortują malejąco (daty i liczniki). */
    public const DESC_FIRST = ['publikacja', 'zmiana', 'komentarze', 'wyswietlenia'];

    public const PER_PAGE = [25, 50, 100];

    public const TOGGLES = [
        'bez_zdjecia'  => 'bez zdjęcia lub tekstu alternatywnego',
        'bez_seo'      => 'bez opisu SEO',
        'do_moderacji' => 'z komentarzami do moderacji',
    ];

    /** @var array<string, mixed> Znormalizowane wartości filtrów (puste = brak). */
    public array $values = [];

    public ?BlogCategory $category = null;

    public ?BlogAuthor $author = null;

    public ?BlogTag $tag = null;

    public function __construct(Request $request)
    {
        $str = fn (string $k) => trim((string) $request->query($k, ''));

        $status = $str('status');
        $format = $str('format');
        $sort = $str('sort');
        $dir = strtolower($str('dir'));
        $perPage = (int) $request->query('na_strone', 25);

        $this->values = [
            'q'          => mb_substr($str('q'), 0, 100),
            'status'     => array_key_exists($status, self::STATUSES) ? $status : '',
            'kategoria'  => '',
            'autor'      => '',
            'tag'        => '',
            'format'     => array_key_exists($format, self::FORMATS) ? $format : '',
            'od'         => $this->date($str('od')),
            'do'         => $this->date($str('do')),
            'sort'       => array_key_exists($sort, self::SORTS) ? $sort : '',
            'dir'        => $dir === 'asc' ? 'asc' : 'desc',
            'na_strone'  => in_array($perPage, self::PER_PAGE, true) ? $perPage : 25,
        ];
        foreach (array_keys(self::TOGGLES) as $toggle) {
            $this->values[$toggle] = $request->boolean($toggle);
        }

        $cat = $str('kategoria');
        if ($cat === 'brak') {
            $this->values['kategoria'] = 'brak';
        } elseif ($cat !== '') {
            $this->category = BlogCategory::where('slug', $cat)->orWhere('id', ctype_digit($cat) ? (int) $cat : 0)->first();
            $this->values['kategoria'] = $this->category ? (string) $this->category->id : '';
        }

        $author = $str('autor');
        if (ctype_digit($author) && ($this->author = BlogAuthor::find((int) $author))) {
            $this->values['autor'] = (string) $this->author->id;
        }

        $tag = $str('tag');
        if ($tag !== '') {
            $this->tag = BlogTag::where('slug', $tag)->orWhere('id', ctype_digit($tag) ? (int) $tag : 0)->first();
            $this->values['tag'] = $this->tag?->slug ?? '';
        }
    }

    private function date(string $value): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d') === $value ? $value : '';
        } catch (\Throwable) {
            return '';
        }
    }

    public function apply(Builder $query): Builder
    {
        $v = $this->values;

        if ($v['q'] !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $v['q']) . '%';
            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('body', 'like', $like));
        }

        if ($v['status'] === 'wip') {
            $query->whereNotNull('wip_mode')->where('wip_mode', '!=', '');
        } elseif ($v['status'] !== '') {
            $query->status($v['status']);
        }

        if ($v['kategoria'] === 'brak') {
            $query->whereNull('category_id');
        } elseif ($this->category) {
            $query->where('category_id', $this->category->id);
        }

        if ($this->author) {
            $query->byAuthor($this->author);
        }

        if ($this->tag) {
            $query->whereHas('tags', fn (Builder $t) => $t->whereKey($this->tag->id));
        }

        if ($v['format'] !== '') {
            $query->where('body_format', $v['format']);
        }

        if ($v['od'] !== '') {
            $query->where('published_at', '>=', $v['od'] . ' 00:00:00');
        }
        if ($v['do'] !== '') {
            $query->where('published_at', '<=', $v['do'] . ' 23:59:59');
        }

        if ($v['bez_zdjecia']) {
            $query->where(fn (Builder $q) => $q
                ->whereNull('image_path')
                ->orWhere('image_path', '')
                ->orWhere(fn (Builder $a) => $a->where('image_decorative', false)
                    ->where(fn (Builder $alt) => $alt->whereNull('image_alt')->orWhere('image_alt', ''))));
        }

        if ($v['bez_seo']) {
            $query->where(fn (Builder $q) => $q->whereNull('meta_description')->orWhere('meta_description', ''));
        }

        if ($v['do_moderacji']) {
            $query->whereHas('comments', fn (Builder $c) => $c->where('is_approved', false));
        }

        return $this->sort($query);
    }

    private function sort(Builder $query): Builder
    {
        $dir = $this->values['dir'];

        $query = match ($this->values['sort']) {
            'tytul'      => $query->orderBy('title', $dir),
            'autor'      => $query->orderByRaw(
                'COALESCE((SELECT name FROM blog_authors WHERE blog_authors.id = blog_articles.author_id), NULLIF(blog_articles.author_name, \'\'), \'\') COLLATE NOCASE ' . $dir
            ),
            'kategoria'  => $query->orderByRaw(
                'COALESCE((SELECT name FROM blog_categories WHERE blog_categories.id = blog_articles.category_id), \'\') COLLATE NOCASE ' . $dir
            ),
            'publikacja' => $query->orderBy('published_at', $dir),
            'zmiana'     => $query->orderBy('updated_at', $dir),
            'komentarze' => $query->orderBy('comments_count', $dir),
            'wyswietlenia' => $query->orderBy('views', $dir),
            default      => $query->orderByDesc('created_at'),
        };

        return $query->orderByDesc('id');
    }

    /** Czy ustawiono jakikolwiek filtr (bez sortowania i liczby na stronę). */
    public function active(): bool
    {
        return $this->summary() !== [];
    }

    /**
     * Aktywne filtry jako zwykły tekst z linkiem „usuń" (adres bez tego filtru).
     *
     * @return array<int, array{label: string, remove: string}>
     */
    public function summary(): array
    {
        $v = $this->values;
        $items = [];
        $add = function (string $key, string $label) use (&$items) {
            $items[] = ['label' => $label, 'remove' => $this->url([$key => null])];
        };

        $labels = [
            'q'      => $v['q'] !== '' ? 'wyszukiwanie „' . $v['q'] . '”' : null,
            'status' => $v['status'] !== '' ? 'status ' . mb_strtolower(self::STATUSES[$v['status']]) : null,
            'kategoria' => $v['kategoria'] === 'brak' ? 'bez kategorii' : ($this->category ? 'kategoria ' . $this->category->name : null),
            'autor'  => $this->author ? 'autor ' . $this->author->name : null,
            'tag'    => $this->tag ? 'tag ' . $this->tag->name : null,
            'format' => $v['format'] !== '' ? 'format ' . self::FORMATS[$v['format']] : null,
            'od'     => $v['od'] !== '' ? 'opublikowane od ' . Carbon::parse($v['od'])->format('d.m.Y') : null,
            'do'     => $v['do'] !== '' ? 'opublikowane do ' . Carbon::parse($v['do'])->format('d.m.Y') : null,
        ];
        foreach (array_filter($labels) as $key => $label) {
            $add($key, $label);
        }
        foreach (self::TOGGLES as $key => $label) {
            if ($v[$key]) {
                $add($key, $label);
            }
        }

        return $items;
    }

    /** Parametry zapytania (bez pustych) — do linków i paginacji. */
    public function query(array $overrides = []): array
    {
        $params = array_merge($this->values, $overrides);
        foreach (array_keys(self::TOGGLES) as $t) {
            $params[$t] = ! empty($params[$t]) ? 1 : null;
        }
        if ((int) ($params['na_strone'] ?? 25) === 25) {
            $params['na_strone'] = null;
        }
        if (($params['sort'] ?? '') === '') {
            $params['sort'] = null;
            $params['dir'] = null;
        }

        return array_filter($params, fn ($p) => $p !== null && $p !== '');
    }

    public function url(array $overrides = []): string
    {
        return route('admin.wiem-feer.index', $this->query($overrides));
    }

    /** Link sortowania kolumny: pierwsze kliknięcie rosnąco (daty i liczniki — malejąco), drugie odwraca. */
    public function sortUrl(string $column): string
    {
        $default = in_array($column, self::DESC_FIRST, true) ? 'desc' : 'asc';
        $dir = $this->values['sort'] === $column ? ($this->values['dir'] === 'asc' ? 'desc' : 'asc') : $default;

        return $this->url(['sort' => $column, 'dir' => $dir]);
    }

    /** Wartość aria-sort dla nagłówka kolumny (null = kolumna nieaktywna). */
    public function ariaSort(string $column): ?string
    {
        return $this->values['sort'] === $column ? ($this->values['dir'] === 'asc' ? 'ascending' : 'descending') : null;
    }
}
