<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnualReport;
use Modules\Blog\Models\BlogArticle;
use App\Models\EducationalMaterial;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\HeroSlide;
use App\Models\LandingPage;
use App\Models\News;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Poll;
use App\Models\Project;
use App\Models\QuickAction;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VolunteerAd;
use App\Support\AdminNotifications;

/**
 * Panel admin: główny pulpit z licznikami treści, ostatnimi wpisami i statusem modułów.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DashboardController extends Controller
{
    /** Wyświetla główny pulpit z licznikami treści, ostatnimi wpisami i statusem modułów. */
    public function index()
    {
        $settings = SiteSetting::current();
        $user = auth()->user();

        $allStats = [
            'pages' => [
                'label' => 'Podstrony',
                'count' => Page::count(),
                'sub'   => Page::where('is_published', true)->count().' opublikowanych',
                'icon'  => 'fa-file-lines',
                'route' => route('admin.podstrony.index'),
            ],
            'news' => [
                'label' => 'Aktualności',
                'count' => News::count(),
                'sub'   => News::published()->count().' opublikowanych',
                'icon'  => 'fa-newspaper',
                'route' => route('admin.newsy.index'),
            ],
            'events' => [
                'label' => 'Szkolenia i wydarzenia',
                'count' => Event::count(),
                'sub'   => Event::where('is_published', true)->count().' opublikowanych',
                'icon'  => 'fa-calendar-days',
                'route' => route('admin.wydarzenia.index'),
            ],
            'projects' => [
                'label' => 'Działania',
                'count' => Project::count(),
                'sub'   => Project::where('is_published', true)->count().' opublikowanych',
                'icon'  => 'fa-diagram-project',
                'route' => route('admin.projekty.index'),
            ],
            'materials' => [
                'label' => 'Materiały edukacyjne',
                'count' => EducationalMaterial::count(),
                'sub'   => EducationalMaterial::where('is_published', true)->count().' opublikowanych',
                'icon'  => 'fa-graduation-cap',
                'route' => route('admin.materialy-edukacyjne.index'),
            ],
            'volunteering' => [
                'label' => 'Wolontariat',
                'count' => VolunteerAd::count(),
                'sub'   => VolunteerAd::where('is_published', true)->count().' aktywnych',
                'icon'  => 'fa-hand-holding-heart',
                'route' => route('admin.wolontariat.index'),
            ],
            'faq' => [
                'label' => 'FAQ',
                'count' => Faq::count(),
                'sub'   => Faq::published()->count().' opublikowanych',
                'icon'  => 'fa-circle-question',
                'route' => route('admin.faq.index'),
            ],
            'reports' => [
                'label' => 'Sprawozdania',
                'count' => AnnualReport::count(),
                'sub'   => AnnualReport::published()->count().' opublikowanych',
                'icon'  => 'fa-file-invoice',
                'route' => route('admin.sprawozdania.index'),
            ],
            'landing' => [
                'label' => 'Landing pages',
                'count' => LandingPage::count(),
                'sub'   => LandingPage::where('is_published', true)->count().' opublikowanych',
                'icon'  => 'fa-bullhorn',
                'route' => route('admin.lp.index'),
            ],
            'gallery' => [
                'label' => 'Galeria',
                'count' => GalleryImage::count(),
                'sub'   => HeroSlide::count().' slajdów hero',
                'icon'  => 'fa-panorama',
                'route' => route('admin.galeria.index'),
            ],
            'polls' => [
                'label' => 'Ankiety',
                'count' => Poll::count(),
                'sub'   => Poll::where('is_active', true)->exists() ? 'jedna aktywna' : 'brak aktywnej',
                'icon'  => 'fa-square-poll-vertical',
                'route' => route('admin.ankiety.index'),
            ],
            'quick_actions' => [
                'label' => 'Szybkie akcje',
                'count' => QuickAction::count(),
                'sub'   => '',
                'icon'  => 'fa-bolt',
                'route' => route('admin.szybkie-akcje.index'),
            ],
            'partners' => [
                'label' => 'Partnerzy',
                'count' => Partner::count(),
                'sub'   => '',
                'icon'  => 'fa-handshake',
                'route' => route('admin.partnerzy.index'),
            ],
        ];

        // Blog na osobnej bazie — pobieramy licznik tylko jeśli moduł aktywny.
        if ($settings->isModuleEnabled('blog')) {
            try {
                $allStats['blog'] = [
                    'label' => 'Wiem FEER (blog)',
                    'count' => BlogArticle::count(),
                    'sub'   => BlogArticle::where('is_published', true)->count().' opublikowanych',
                    'icon'  => 'fa-feather-pointed',
                    'route' => route('admin.wiem-feer.index'),
                ];
            } catch (\Exception) {
                // Baza bloga niedostępna — kafelek pomijamy.
            }
        }

        $stats = collect($allStats)->filter(
            fn ($stat, $module) => $settings->isModuleEnabled($module) && $user->canAccessModule($module)
        );

        if ($user->isAdmin()) {
            $stats->push([
                'label' => 'Użytkownicy',
                'count' => User::count(),
                'sub'   => User::where('role', User::ROLE_ADMIN)->count().' administratorów',
                'icon'  => 'fa-users',
                'route' => route('admin.uzytkownicy.index'),
            ]);
        }

        $can = fn (string $module) => $settings->isModuleEnabled($module) && $user->canAccessModule($module);

        $recentNews  = $can('news')  ? News::with('category')->orderByDesc('created_at')->limit(5)->get() : collect();
        $recentPages = $can('pages') ? Page::orderByDesc('created_at')->limit(5)->get() : collect();
        $activePoll  = $can('polls') ? Poll::active() : null;

        // „Wymaga uwagi": zatwierdzanie, zadania, komentarze, zgłoszenia — z uwzględnieniem uprawnień użytkownika.
        $attention = collect(AdminNotifications::items($user));

        // Aktywność: ostatnio zmieniane treści z kilku modułów w jednej osi czasu (zakres edytora stosuje model).
        $sources = [
            ['module' => 'news',     'label' => 'Aktualność', 'icon' => 'fa-newspaper',      'tone' => 'blue',   'model' => News::class,    'edit' => 'admin.newsy.edit'],
            ['module' => 'pages',    'label' => 'Strona',     'icon' => 'fa-file-lines',     'tone' => 'purple', 'model' => Page::class,    'edit' => 'admin.podstrony.edit'],
            ['module' => 'projects', 'label' => 'Działanie',    'icon' => 'fa-diagram-project','tone' => 'green',  'model' => Project::class, 'edit' => 'admin.projekty.edit'],
            ['module' => 'events',   'label' => 'Wydarzenie', 'icon' => 'fa-calendar-days',  'tone' => 'amber',  'model' => Event::class,   'edit' => 'admin.wydarzenia.edit'],
        ];
        $activity = collect();
        $draftCounts = [];
        foreach ($sources as $src) {
            if (! $can($src['module'])) {
                continue;
            }
            $model = $src['model'];
            $drafts = $model::where('is_published', false)->count();
            if ($drafts > 0) {
                $draftCounts[] = ['label' => $src['label'], 'count' => $drafts];
            }
            foreach ($model::orderByDesc('updated_at')->limit(8)->get() as $row) {
                $activity->push([
                    'type' => $src['module'], 'label' => $src['label'], 'icon' => $src['icon'], 'tone' => $src['tone'],
                    'title' => $row->title, 'url' => route($src['edit'], $row), 'at' => $row->updated_at,
                    'published' => (bool) $row->is_published, 'author_id' => $row->created_by ?? null,
                ]);
            }
        }
        $authors = User::whereIn('id', $activity->pluck('author_id')->filter()->unique())->pluck('name', 'id');
        $activity = $activity->sortByDesc('at')->take(14)->values()->map(function ($a) use ($authors) {
            $a['author'] = $a['author_id'] ? $authors->get($a['author_id']) : null;

            return $a;
        });

        $upcoming = $can('events')
            ? Event::where('is_published', true)->where('starts_at', '>=', now())->orderBy('starts_at')->limit(4)->get()
            : collect();

        return view('admin.dashboard', compact('stats', 'recentNews', 'recentPages', 'activePoll', 'attention', 'activity', 'draftCounts', 'upcoming'));
    }
}
