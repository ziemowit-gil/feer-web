<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BlogAdmin;
use Illuminate\Database\QueryException;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogAuthor;
use Modules\Blog\Models\BlogComment;

/**
 * Panel admin: osobny pulpit bloga „Wiem FEER" — liczniki wpisów, lista „Do zrobienia”
 * (moderacja, stare szkice, braki SEO/dostępności, profile autorów), zaplanowane
 * publikacje i ostatnio edytowane wpisy.
 *
 * Blog ma własną bazę (połączenie `blog`) — gdy jej brak, pulpit pokazuje komunikat
 * zamiast błędu. Liczba wyświetleń nie jest śledzona, więc nie ma sekcji „Najpopularniejsze”.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class BlogDashboardController extends Controller
{
    /** Ile dni bez zmian, by szkic trafił na listę „Do zrobienia”. */
    public const STALE_DRAFT_DAYS = 14;

    /** Zasięg paska nadchodzących publikacji (dni). */
    public const CALENDAR_DAYS = 14;

    /** Wyświetla pulpit bloga. */
    public function index()
    {
        abort_unless(BlogAdmin::canAccess(auth()->user()), 403);

        try {
            $data = $this->collect();
            $dbError = false;
        } catch (QueryException) {
            $data = [];
            $dbError = true;
        }

        return view('admin.blog-dashboard', $data + [
            'dbError'   => $dbError,
            'publicUrl' => BlogAdmin::publicUrl(),
            'staleDays' => self::STALE_DRAFT_DAYS,
        ]);
    }

    /** @return array<string, mixed> */
    private function collect(): array
    {
        $now = now();

        $stats = [
            ['label' => 'Opublikowane', 'count' => BlogArticle::query()->status('published')->count(), 'url' => route('admin.wiem-feer.index', ['status' => 'published'])],
            ['label' => 'Zaplanowane', 'count' => BlogArticle::query()->status('scheduled')->count(), 'url' => route('admin.wiem-feer.index', ['status' => 'scheduled'])],
            ['label' => 'Szkice', 'count' => BlogArticle::query()->status('draft')->count(), 'url' => route('admin.wiem-feer.index', ['status' => 'draft'])],
            ['label' => 'Wyłączone lub w przygotowaniu', 'count' => BlogArticle::where(fn ($q) => $q
                ->where('is_disabled', true)
                ->orWhereIn('wip_mode', array_keys(BlogArticle::WIP_MODES)))->count(), 'url' => route('admin.wiem-feer.index', ['status' => 'disabled'])],
            ['label' => 'Komentarze do moderacji', 'count' => BlogComment::pending()->count(), 'url' => route('admin.komentarze-bloga.index')],
            ['label' => 'Aktywni autorzy', 'count' => BlogAuthor::active()->count(), 'url' => route('admin.autorzy-bloga.index')],
        ];

        $pendingComments = BlogComment::with('article')->pending()->orderByDesc('created_at')->limit(5)->get();

        $staleDrafts = BlogArticle::query()->status('draft')
            ->where('updated_at', '<', $now->copy()->subDays(self::STALE_DRAFT_DAYS))
            ->orderBy('updated_at')
            ->limit(10)
            ->get();

        // Lista kontrolna SEO i dostępności dla opublikowanych wpisów.
        $checklist = BlogArticle::query()->status('published')->latest('published_at')->get()
            ->map(fn (BlogArticle $a) => ['article' => $a, 'issues' => $this->issues($a)])
            ->filter(fn (array $row) => $row['issues'] !== [])
            ->values();

        $incompleteAuthors = BlogAuthor::active()->orderBy('name')->get()
            ->map(fn (BlogAuthor $author) => ['author' => $author, 'missing' => array_values(array_filter([
                blank(trim((string) $author->bio)) ? 'bio' : null,
                blank($author->photo_path) ? 'zdjęcie' : null,
            ]))])
            ->filter(fn (array $row) => $row['missing'] !== [])
            ->values();

        $scheduled = BlogArticle::query()->status('scheduled')->orderBy('published_at')->limit(10)->get();
        $recent = BlogArticle::orderByDesc('updated_at')->limit(5)->get();

        // Pasek najbliższych dni z zaplanowanymi wpisami.
        $byDay = BlogArticle::query()->status('scheduled')
            ->where('published_at', '<', $now->copy()->startOfDay()->addDays(self::CALENDAR_DAYS))
            ->orderBy('published_at')
            ->get()
            ->groupBy(fn (BlogArticle $a) => $a->published_at->toDateString());
        $calendar = collect(range(0, self::CALENDAR_DAYS - 1))->map(function (int $i) use ($now, $byDay) {
            $day = $now->copy()->startOfDay()->addDays($i);

            return ['date' => $day, 'articles' => $byDay->get($day->toDateString(), collect())];
        });

        return compact('stats', 'pendingComments', 'staleDrafts', 'checklist', 'incompleteAuthors', 'scheduled', 'recent', 'calendar');
    }

    /**
     * Braki opublikowanego wpisu: opis alternatywny zdjęcia, kategoria, zajawka, opis meta.
     *
     * @return array<int, string>
     */
    private function issues(BlogArticle $article): array
    {
        return array_values(array_filter([
            $article->hasImage() && ! $article->image_decorative && blank(trim((string) $article->image_alt))
                ? 'brak tekstu alternatywnego zdjęcia' : null,
            blank($article->category_id) ? 'brak kategorii' : null,
            blank(trim((string) $article->excerpt)) ? 'brak zajawki' : null,
            blank(trim((string) $article->meta_description)) ? 'brak opisu meta' : null,
        ]));
    }
}
