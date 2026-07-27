<?php

namespace App\Http\Controllers;

use App\Models\BlogArticle;
use App\Models\EducationalMaterial;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /** Minimalna długość zapytania, żeby nie zwracać całego serwisu. */
    private const MIN = 2;

    /** Limit wyników na kategorię. */
    private const PER_GROUP = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $groups = [];
        $searched = mb_strlen($q) >= self::MIN;

        if ($searched) {
            $settings = SiteSetting::current();
            $like = '%'.$this->escapeLike($q).'%';

            if ($settings->isModuleEnabled('pages')) {
                $groups['Strony'] = Page::where('is_published', true)->where('is_disabled', false)
                    ->whereNotIn('type', ['internal', 'internal_hub'])
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('content', 'like', $like))
                    ->orderBy('title')->limit(self::PER_GROUP)->get()
                    ->map(fn ($p) => $this->item($p->title, route('page.show', $p), $p->content));
            }

            if ($settings->isModuleEnabled('news')) {
                $groups['Aktualności'] = News::published()
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('content', 'like', $like))
                    ->orderByDesc('published_at')->limit(self::PER_GROUP)->get()
                    ->map(fn ($n) => $this->item($n->title, route('news.show', $n), $n->excerpt ?: $n->content));
            }

            if ($settings->isModuleEnabled('projects')) {
                $groups['Projekty'] = Project::where('is_published', true)
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('content', 'like', $like)->orWhere('for_whom', 'like', $like))
                    ->orderBy('title')->limit(self::PER_GROUP)->get()
                    ->map(fn ($p) => $this->item($p->title, route('projects.show', $p), $p->excerpt ?: $p->content));
            }

            if ($settings->isModuleEnabled('materials')) {
                $groups['Materiały edukacyjne'] = EducationalMaterial::where('is_published', true)->where('is_archival', false)
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like)->orWhere('target_group', 'like', $like))
                    ->orderBy('order')->limit(self::PER_GROUP)->get()
                    ->map(fn ($m) => $this->item($m->title, route('materials.index'), $m->description));
            }

            // Blog działa na osobnym połączeniu — gdyby było niedostępne, nie
            // przerywamy całej wyszukiwarki.
            try {
                $groups['Wiem FEER (blog)'] = BlogArticle::where('is_published', true)->where('is_disabled', false)
                    ->where('published_at', '<=', now())
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
                    ->orderByDesc('published_at')->limit(self::PER_GROUP)->get()
                    ->map(fn ($a) => $this->item($a->title, route('blog.show', $a), $a->excerpt ?: $a->body));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Odrzuć puste grupy i policz łączną liczbę trafień.
        $groups = array_filter($groups, fn ($g) => $g->isNotEmpty());
        $total = collect($groups)->sum(fn ($g) => $g->count());

        return view('search.index', compact('q', 'groups', 'total', 'searched'));
    }

    private function item(string $title, string $url, ?string $body): array
    {
        return [
            'title' => $title,
            'url' => $url,
            'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $body))), 160),
        ];
    }

    /** Zabezpiecz znaki specjalne LIKE (% _ \) w zapytaniu użytkownika. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
