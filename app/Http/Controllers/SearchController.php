<?php

namespace App\Http\Controllers;

use Modules\Blog\Models\BlogArticle;
use App\Models\EducationalMaterial;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Publiczna wyszukiwarka serwisu — przeszukuje strony, aktualności, działania,
 * materiały edukacyjne i artykuły bloga z paginacją i podświetlaniem trafień.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SearchController extends Controller
{
    /** Minimalna długość zapytania, żeby nie zwracać całego serwisu. */
    private const MIN = 2;

    /** Limit wyników na kategorię. */
    private const PER_GROUP = 20;

    /** Przeszukuje opublikowane treści serwisu i zwraca wyniki pogrupowane wg sekcji. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $archive = (bool) $request->query('archiwum', false);
        $typ = in_array($request->query('typ'), ['aktualnosci', 'materialy']) ? $request->query('typ') : '';
        $groups = [];
        $searched = mb_strlen($q) >= self::MIN;

        if ($searched) {
            $settings = SiteSetting::current();
            $like = '%'.$this->escapeLike($q).'%';

            if ($typ === '' && $settings->isModuleEnabled('pages')) {
                $groups['Strony'] = Page::forCurrentSite()->where('is_published', true)->where('is_disabled', false)
                    // Podstrony wyłączonego działu są niedostępne, więc nie pokazujemy ich w wynikach.
                    ->whereNotIn('id', array_keys(Page::inheritedDisabledMap()))
                    ->whereNotIn('type', ['internal', 'internal_hub'])
                    // type_data (oferta, poradnik, słownik, studium) i faq_items to JSON —
                    // LIKE po surowej kolumnie wystarcza, by hasła słownika czy kroki
                    // poradnika były wyszukiwalne bez osobnego indeksu.
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('content', 'like', $like)
                        ->orWhere('type_data', 'like', $like)->orWhere('faq_items', 'like', $like))
                    ->orderBy('title')->limit(self::PER_GROUP)->get()
                    ->map(fn ($p) => $this->item($p->title, route('page.show', $p), $p->content ?: $p->meta_description, $p->created_at));
            }

            if (($typ === '' || $typ === 'aktualnosci') && $settings->isModuleEnabled('news')) {
                // Bez scope'u published(): archiwalne aktualności są nadal opublikowane
                // i mają być wyszukiwalne (patrz ArchiveTest).
                $groups['Aktualności'] = News::forCurrentSite()
                    ->where('is_published', true)->where('published_at', '<=', now())
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('content', 'like', $like))
                    ->orderByDesc('published_at')->limit(self::PER_GROUP)->get()
                    ->map(fn ($n) => $this->item($n->title, route('news.show', $n), $n->excerpt ?: $n->content, $n->published_at));
            }

            if ($typ === '' && $settings->isModuleEnabled('projects')) {
                $groups['Działania'] = Project::forCurrentSite()->where('is_published', true)
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('content', 'like', $like)->orWhere('for_whom', 'like', $like))
                    ->orderBy('title')->limit(self::PER_GROUP)->get()
                    ->map(fn ($p) => $this->item($p->title, route('projects.show', $p), $p->excerpt ?: $p->content, $p->created_at));
            }

            if (($typ === '' || $typ === 'materialy') && $settings->isModuleEnabled('materials')) {
                $materialsQuery = EducationalMaterial::where('is_published', true)
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('target_group', 'like', $like))
                    ->orderBy('order')->limit(self::PER_GROUP);

                if (! $archive) {
                    $materialsQuery->where('is_archival', false);
                }

                $label = $archive ? 'Materiały edukacyjne (z archiwum)' : 'Materiały edukacyjne';
                $groups[$label] = $materialsQuery->get()
                    ->map(fn ($m) => $this->item($m->title, route('materials.index'), $m->description, $m->created_at, null, $m->is_archival));
            }

            // Wiem FEER (blog) działa na osobnym połączeniu — gdy baza bloga jest
            // niedostępna (brak pliku/tabel), pomijamy grupę zamiast przerywać wyszukiwarkę.
            if ($typ === '') {
                try {
                    $groups['Wiem FEER'] = BlogArticle::listed()
                        ->with(['author', 'coAuthors'])
                        ->where(fn ($w) => $w->whereRaw("title LIKE ? ESCAPE '\\'", [$like])
                            ->orWhereRaw("excerpt LIKE ? ESCAPE '\\'", [$like])
                            ->orWhereRaw("body LIKE ? ESCAPE '\\'", [$like]))
                        ->latestFirst()->limit(self::PER_GROUP)->get()
                        ->map(fn (BlogArticle $a) => $this->item($a->title, route('blog.show', $a), null, $a->publishedDate(), $a->authorLine() ?: null)
                            + ['snippet_html' => $this->highlightedSnippet($q, (string) $a->excerpt, (string) $a->body)]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        // Odrzuć puste grupy i policz łączną liczbę trafień.
        $groups = array_filter($groups, fn ($g) => $g->isNotEmpty());
        $total = collect($groups)->sum(fn ($g) => $g->count());

        return view('search.index', compact('q', 'groups', 'total', 'searched', 'archive', 'typ'));
    }

    private function item(string $title, string $url, ?string $body, ?\Illuminate\Support\Carbon $date = null, ?string $author = null, bool $archival = false): array
    {
        return [
            'title' => $title,
            'url' => $url,
            'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $body))), 160),
            'date' => $date,
            'author' => $author,
            'archival' => $archival,
        ];
    }

    /**
     * Fragment tekstu z podświetloną frazą (<mark>): zajawka, gdy zawiera frazę,
     * w przeciwnym razie okolica pierwszego trafienia w treści. Tekst jest
     * escapowany przed wstawieniem znaczników, więc wynik można wypisać przez {!! !!}.
     */
    private function highlightedSnippet(string $q, string $excerpt, string $body, int $length = 200): string
    {
        $plain = fn (string $html) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $excerpt = $plain($excerpt);
        $body = $plain($body);

        if ($excerpt !== '' && mb_stripos($excerpt, $q) !== false) {
            $text = Str::limit($excerpt, $length);
        } elseif (($pos = mb_stripos($body, $q)) !== false) {
            $start = max(0, $pos - (int) floor(($length - mb_strlen($q)) / 2));
            $text = ($start > 0 ? '… ' : '') . mb_substr($body, $start, $length) . ($start + $length < mb_strlen($body) ? ' …' : '');
        } else {
            $text = Str::limit($excerpt !== '' ? $excerpt : $body, $length);
        }

        // Dzielimy surowy tekst na trafienia i resztę, escapujemy każdy kawałek osobno —
        // fraza nie trafi w środek encji HTML (np. „amp” w &amp;).
        $pieces = preg_split('/(' . preg_quote($q, '/') . ')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];

        return collect($pieces)
            ->map(fn (string $piece, int $i) => $i % 2 === 1 ? '<mark>' . e($piece) . '</mark>' : e($piece))
            ->implode('');
    }

    /** Zabezpiecz znaki specjalne LIKE (% _ \) w zapytaniu użytkownika. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
