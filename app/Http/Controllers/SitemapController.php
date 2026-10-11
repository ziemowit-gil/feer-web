<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Generuje plik XML sitemap (dla robotów) oraz czytelną stronę HTML „Mapa strony".
 *
 * Metody: index(), page().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class SitemapController extends Controller
{
    /** Generuje XML sitemap z opublikowanych treści z uwzględnieniem włączonych modułów. */
    public function index()
    {
        $settings = SiteSetting::current();
        $sitemap = Sitemap::create();

        $sitemap->add(Url::create(route('home'))->setPriority(1.0));
        $sitemap->add(Url::create(route('contact.show')));
        $sitemap->add(Url::create(route('newsletter.show')));

        if ($settings->isModuleEnabled('support')) {
            $sitemap->add(Url::create(route('support.show')));
        }

        if ($settings->isModuleEnabled('materials')) {
            $sitemap->add(Url::create(route('materials.index')));
        }

        if ($settings->isModuleEnabled('news')) {
            $sitemap->add(Url::create(route('news.index')));

            News::query()->where('is_published', true)->where('published_at', '<=', now())->get()
                ->each(fn (News $news) => $sitemap->add(
                    Url::create(route('news.show', $news))->setLastModificationDate($news->updated_at)
                ));
        }

        // Wiem FEER (blog na osobnej bazie) — tylko gdy moduł aktywny i baza dostępna.
        if ($settings->isModuleEnabled('blog') && class_exists(\Modules\Blog\Models\BlogArticle::class)) {
            try {
                $sitemap->add(Url::create(route('blog.index')));
                \Modules\Blog\Models\BlogArticle::query()->listed()->get()
                    ->each(fn ($article) => $sitemap->add(
                        Url::create(route('blog.show', $article))->setLastModificationDate($article->updated_at)
                    ));
            } catch (\Throwable) {
                // Baza bloga niedostępna — pomijamy jego adresy.
            }
        }

        if ($settings->isModuleEnabled('projects')) {
            $sitemap->add(Url::create(route('projects.index')));

            Project::query()->where('is_published', true)->get()
                ->each(fn (Project $project) => $sitemap->add(
                    Url::create(route('projects.show', $project))->setLastModificationDate($project->updated_at)
                ));

            Category::query()->whereHas('projects', fn ($query) => $query->where('is_published', true))->get()
                ->each(fn (Category $category) => $sitemap->add(Url::create(route('categories.show', $category))));
        }

        if ($settings->isModuleEnabled('pages')) {
            Page::query()->where('is_published', true)->get()
                ->each(fn (Page $page) => $sitemap->add(
                    Url::create(route('page.show', $page))->setLastModificationDate($page->updated_at)
                ));
        }

        return $sitemap->toResponse(request());
    }

    /**
     * Czytelna strona HTML „Mapa strony": kafle głównych działów (wg włączonych modułów),
     * drzewo stron witryny (rodzic → podstrony), osoby, działania i ostatnie aktualności.
     * Pomija strony niepubliczne: wyłączone (także przez przodka), zarchiwizowane,
     * zaplanowane na później i chronione (hasło, strefa wewnętrzna, grupy).
     */
    public function page()
    {
        $settings = SiteSetting::current();
        $on = fn (string $module) => $settings->isModuleEnabled($module);
        $has = fn (string $route) => \Illuminate\Support\Facades\Route::has($route);

        // Kafle: [etykieta, adres, ikona, opis].
        $sections = array_values(array_filter([
            ['Strona główna', route('home'), 'fa-house', 'Start serwisu'],
            $on('projects') && $has('projects.index') ? ['Działania', route('projects.index'), 'fa-diagram-project', 'Projekty i programy'] : null,
            $on('news') && $has('news.index') ? ['Aktualności', route('news.index'), 'fa-newspaper', 'Wiadomości i komunikaty'] : null,
            $on('events') && $has('events.index') ? ['Wydarzenia', route('events.index'), 'fa-calendar-days', 'Szkolenia, spotkania, webinary'] : null,
            $on('materials') && $has('materials.index') ? ['Materiały edukacyjne', route('materials.index'), 'fa-book-open', 'Poradniki i materiały do pobrania'] : null,
            $on('blog') && $has('blog.index') ? ['Wiem FEER', route('blog.index'), 'fa-feather-pointed', 'Blog o dostępności i empatii'] : null,
            $on('volunteering') && $has('volunteer.index') ? ['Wolontariat', route('volunteer.index'), 'fa-hands-holding-child', 'Oferty wolontariatu'] : null,
            $on('jobs') && $has('praca.index') ? ['Praca', route('praca.index'), 'fa-briefcase', 'Oferty pracy i współpracy'] : null,
            $on('support') && $has('support.show') ? ['Wesprzyj nas', route('support.show'), 'fa-hand-holding-heart', 'Darowizny i 1,5% podatku'] : null,
            $on('faq') && $has('faq.index') ? ['Pytania i odpowiedzi', route('faq.index'), 'fa-circle-question', 'FAQ'] : null,
            $on('bip') && $has('bip') ? ['BIP', route('bip'), 'fa-landmark', 'Biuletyn Informacji Publicznej'] : null,
            $has('newsletter.show') ? ['Newsletter', route('newsletter.show'), 'fa-envelope-open-text', 'Zapisz się na wiadomości'] : null,
            $has('contact.show') ? ['Kontakt', route('contact.show'), 'fa-address-book', 'Adres, telefon, formularz'] : null,
        ]));

        $tree = collect();
        $people = collect();
        if ($on('pages')) {
            $all = Page::query()->forCurrentSite()->live()
                ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                ->orderBy('order')->orderBy('title')
                ->get();
            $disabledByAncestor = Page::inheritedDisabledMap(Page::query()->forCurrentSite()->get(['id', 'parent_id', 'is_disabled']));
            $visible = $all->reject(fn (Page $p) => $p->is_disabled || isset($disabledByAncestor[$p->id]) || $p->requiresAccess());

            [$people, $pages] = $visible->partition(fn (Page $p) => $p->isAboutPerson());
            $people = $people->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)->values();

            // Drzewo: korzenie to strony bez rodzica albo z rodzicem niewidocznym na mapie.
            $byParent = $pages->groupBy(fn (Page $p) => (int) $p->parent_id);
            $ids = $pages->pluck('id')->flip();
            $build = function ($nodes) use (&$build, $byParent) {
                return $nodes->map(fn (Page $p) => [
                    'page' => $p,
                    'children' => $build($byParent->get($p->id, collect())),
                ])->values();
            };
            $tree = $build($pages->filter(fn (Page $p) => ! $p->parent_id || ! isset($ids[$p->parent_id])));
        }

        return view('sitemap', [
            'sections' => $sections,
            'tree' => $tree,
            'people' => $people,
            'projects' => $on('projects')
                ? Project::query()->forCurrentSite()->where('is_published', true)->orderBy('title')->get()
                : collect(),
            'news' => $on('news')
                ? News::query()->forCurrentSite()->where('is_published', true)->where('published_at', '<=', now())->latest('published_at')->limit(12)->get()
                : collect(),
            'newsUrl' => $on('news') && $has('news.index') ? route('news.index') : null,
        ]);
    }
}
