<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\News;
use App\Models\Page;
use App\Models\Poll;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::current();
        $user = auth()->user();

        $stats = collect([
            'pages' => [
                'label' => 'Podstrony',
                'count' => Page::count(),
                'sub' => Page::where('is_published', true)->count().' opublikowanych',
                'icon' => 'fa-file-lines',
                'route' => route('admin.podstrony.index'),
            ],
            'news' => [
                'label' => 'Aktualności',
                'count' => News::count(),
                'sub' => News::published()->count().' opublikowanych',
                'icon' => 'fa-newspaper',
                'route' => route('admin.newsy.index'),
            ],
            'projects' => [
                'label' => 'Projekty',
                'count' => Project::count(),
                'sub' => Project::where('is_published', true)->count().' opublikowanych',
                'icon' => 'fa-diagram-project',
                'route' => route('admin.projekty.index'),
            ],
            'gallery' => [
                'label' => 'Galeria',
                'count' => GalleryImage::count(),
                'sub' => HeroSlide::count().' slajdów hero',
                'icon' => 'fa-panorama',
                'route' => route('admin.galeria.index'),
            ],
            'polls' => [
                'label' => 'Ankiety',
                'count' => Poll::count(),
                'sub' => Poll::where('is_active', true)->exists() ? 'jedna aktywna' : 'brak aktywnej',
                'icon' => 'fa-square-poll-vertical',
                'route' => route('admin.ankiety.index'),
            ],
        ])->filter(fn ($stat, $module) => $settings->isModuleEnabled($module) && $user->canAccessModule($module));

        if ($user->isAdmin()) {
            $stats->push([
                'label' => 'Użytkownicy',
                'count' => User::count(),
                'sub' => User::where('role', User::ROLE_ADMIN)->count().' administratorów',
                'icon' => 'fa-users',
                'route' => route('admin.uzytkownicy.index'),
            ]);
        }

        $canSeeNews = $settings->isModuleEnabled('news') && $user->canAccessModule('news');
        $canSeePages = $settings->isModuleEnabled('pages') && $user->canAccessModule('pages');
        $canSeePolls = $settings->isModuleEnabled('polls') && $user->canAccessModule('polls');

        $recentNews = $canSeeNews ? News::with('category')->orderByDesc('created_at')->limit(5)->get() : collect();
        $recentPages = $canSeePages ? Page::orderByDesc('created_at')->limit(5)->get() : collect();
        $activePoll = $canSeePolls ? Poll::active() : null;

        return view('admin.dashboard', compact('stats', 'recentNews', 'recentPages', 'activePoll'));
    }
}
