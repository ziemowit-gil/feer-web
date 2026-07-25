<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    /**
     * Build the sitemap from published content, honouring which modules are
     * enabled so disabled sections (which 404) never appear in it.
     */
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
     * Human-readable "Mapa strony" page listing the same published content as
     * the XML sitemap, grouped by section (enabled modules only).
     */
    public function page()
    {
        $settings = SiteSetting::current();

        return view('sitemap', [
            'pages' => $settings->isModuleEnabled('pages')
                ? Page::query()->where('is_published', true)->orderBy('title')->get()
                : collect(),
            'news' => $settings->isModuleEnabled('news')
                ? News::query()->where('is_published', true)->where('published_at', '<=', now())->latest('published_at')->get()
                : collect(),
            'projects' => $settings->isModuleEnabled('projects')
                ? Project::query()->where('is_published', true)->orderBy('title')->get()
                : collect(),
            'materialsEnabled' => $settings->isModuleEnabled('materials'),
            'supportEnabled' => $settings->isModuleEnabled('support'),
        ]);
    }
}
