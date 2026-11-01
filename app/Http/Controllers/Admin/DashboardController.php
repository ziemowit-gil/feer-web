<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\News;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminMenu;
use App\Support\AdminNotifications;
use App\Support\BlogAdmin;
use Illuminate\Support\Facades\Route;

/**
 * Panel admin: pulpit — co czeka na decyzję, skróty „Utwórz”, szkice do dokończenia, ostatnia aktywność i najbliższe
 * wydarzenia. Wszystko z uwzględnieniem roli; liczniki treści i skróty do modułów są w menu bocznym, nie tutaj.
 *
 * Metody: index().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class DashboardController extends Controller
{
    /** Najwięcej pozycji w każdej liście pulpitu — pulpit ma być przeglądem, nie archiwum. */
    private const DRAFTS_LIMIT = 5;

    private const ACTIVITY_LIMIT = 6;

    private const UPCOMING_LIMIT = 3;

    /** Wyświetla pulpit: sprawy „Na dziś”, skróty „Utwórz”, szkice, ostatnią aktywność i najbliższe wydarzenia. */
    public function index()
    {
        $settings = SiteSetting::current();
        $user = auth()->user();

        // Po zalogowaniu administrator sieci witryn wybiera, nad którą pracuje (raz na sesję).
        if (! session('admin_site_chosen') && ! session('admin_active_site_id') && (new \App\Http\Middleware\EnsureCanManageSites)->allows($user)
            && \App\Http\Controllers\Admin\SiteChooserController::sites()->count() > 1) {
            return redirect()->route('admin.wybor-witryny');
        }

        // Aktywna witryna bloga „Wiem FEER” ma własny pulpit.
        if (BlogAdmin::isBlogSiteActive() && BlogAdmin::canAccess($user)) {
            return redirect()->route('admin.wiem.dashboard');
        }

        $can = fn (string $module) => $settings->isModuleEnabled($module) && $user->canAccessModule($module);

        // „Wymaga uwagi": zatwierdzanie, zadania, komentarze, zgłoszenia — z uwzględnieniem uprawnień użytkownika.
        $attention = collect(AdminNotifications::items($user));
        // Na pulpicie dodatkowo: treści do przeglądu (12 miesięcy bez zmian) i nieprzeczytane zgłoszenia z formularzy.
        if (($can('pages') || $can('news') || $can('projects')) && \App\Support\ContentReview::available() && Route::has('admin.przeglad-tresci.index')) {
            $stale = \App\Support\ContentReview::staleCount(fn ($m) => $user->canAccessModule($m));
            if ($stale > 0) {
                $attention->push(['label' => 'Treści do przeglądu', 'count' => $stale, 'url' => route('admin.przeglad-tresci.index'), 'icon' => 'fa-calendar-check']);
            }
        }
        if ($can('forms') && Route::has('admin.formularze.index')) {
            $unread = \App\Models\FormSubmission::forCurrentSite()->whereNull('read_at')->count();
            if ($unread > 0) {
                $attention->push(['label' => 'Nowe zgłoszenia z formularzy', 'count' => $unread, 'url' => route('admin.formularze.index'), 'icon' => 'fa-inbox']);
            }
        }

        // Szkice do dokończenia i ostatnia aktywność: treści z kilku modułów w jednej osi czasu (zakres edytora stosuje model).
        $sources = [
            ['module' => 'news',     'label' => 'Aktualność', 'icon' => 'fa-newspaper',       'model' => News::class,    'edit' => 'admin.newsy.edit'],
            ['module' => 'pages',    'label' => 'Strona',     'icon' => 'fa-file-lines',      'model' => Page::class,    'edit' => 'admin.podstrony.edit'],
            ['module' => 'projects', 'label' => 'Działanie',  'icon' => 'fa-diagram-project', 'model' => Project::class, 'edit' => 'admin.projekty.edit'],
            ['module' => 'events',   'label' => 'Wydarzenie', 'icon' => 'fa-calendar-days',   'model' => Event::class,   'edit' => 'admin.wydarzenia.edit'],
        ];
        $activity = collect();
        $drafts = collect();
        $tracksContent = false;
        foreach ($sources as $src) {
            if (! $can($src['module'])) {
                continue;
            }
            $tracksContent = true;
            $model = $src['model'];
            foreach ($model::forCurrentSite()->where('is_published', false)->orderByDesc('updated_at')->limit(self::DRAFTS_LIMIT)->get() as $row) {
                $drafts->push(['label' => $src['label'], 'icon' => $src['icon'], 'title' => $row->title, 'url' => route($src['edit'], $row), 'at' => $row->updated_at]);
            }
            foreach ($model::forCurrentSite()->orderByDesc('updated_at')->limit(self::ACTIVITY_LIMIT)->get() as $row) {
                $activity->push([
                    'type' => $src['module'], 'label' => $src['label'], 'icon' => $src['icon'],
                    'title' => $row->title, 'url' => route($src['edit'], $row), 'at' => $row->updated_at,
                    'published' => (bool) $row->is_published, 'author_id' => $row->created_by ?? null,
                ]);
            }
        }
        $drafts = $drafts->sortByDesc('at')->take(self::DRAFTS_LIMIT)->values();
        $activity = $activity->sortByDesc('at')->take(self::ACTIVITY_LIMIT)->values();
        $authors = User::whereIn('id', $activity->pluck('author_id')->filter()->unique())->pluck('name', 'id');
        $activity = $activity->map(function ($a) use ($authors) {
            $a['author'] = $a['author_id'] ? $authors->get($a['author_id']) : null;

            return $a;
        });

        $upcoming = $can('events')
            ? Event::forCurrentSite()->where('is_published', true)->where('starts_at', '>=', now())->orderBy('starts_at')->limit(self::UPCOMING_LIMIT)->get()
            : collect();

        $shortcuts = $this->shortcuts($user);

        return view('admin.dashboard', compact('attention', 'shortcuts', 'activity', 'drafts', 'upcoming', 'tracksContent'));
    }

    /**
     * „Utwórz”: skróty do dodawania treści, które rola może dodać (moduł włączony, uprawnienie, trasa istnieje),
     * od najczęstszych. Klasyczny pulpit pokazuje je w liście rozwijanej, Metro jako kafle (pierwsze osiem).
     *
     * @return array<int, array{route: string, label: string, icon: string}>
     */
    private function shortcuts(User $user): array
    {
        $allowed = fn (string $module): bool => AdminMenu::moduleAllowed($user, $module);

        return collect([
            // [moduł, trasa, etykieta, ikona, dodatkowe uprawnienie]
            ['news', 'admin.newsy.create', 'Aktualność', 'fa-newspaper', null],
            ['pages', 'admin.podstrony.create', 'Strona', 'fa-file-lines', null],
            ['projects', 'admin.projekty.create', 'Działanie', 'fa-diagram-project', null],
            ['events', 'admin.wydarzenia.create', 'Wydarzenie', 'fa-calendar-days', null],
            ['courses', 'admin.kursy.create', 'Szkolenie', 'fa-chalkboard-user', null],
            ['newsletter', 'admin.newsletter.kampanie.create', 'Kampania newslettera', 'fa-envelope', 'newsletter.campaigns'],
            ['bip', 'admin.bip-dokumenty.create', 'Dokument BIP', 'fa-landmark', null],
            ['volunteering', 'admin.wolontariat.create', 'Ogłoszenie wolontariackie', 'fa-hands-helping', null],
            ['jobs', 'admin.praca.create', 'Ogłoszenie o pracę', 'fa-briefcase', null],
            ['landing', 'admin.lp.create', 'Landing page', 'fa-bullhorn', null],
            ['reports', 'admin.sprawozdania.create', 'Sprawozdanie', 'fa-file-invoice', null],
            ['blog', 'admin.wiem-feer.create', 'Wpis bloga', 'fa-feather-pointed', null],
        ])
            ->filter(fn (array $s) => Route::has($s[1]) && $allowed($s[0]) && ($s[4] === null || $user->can($s[4])))
            ->map(fn (array $s) => ['route' => route($s[1]), 'label' => $s[2], 'icon' => $s[3]])
            ->values()
            ->all();
    }
}
