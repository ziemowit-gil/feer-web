<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;

/**
 * Publiczne listy projektów (bieżące, archiwalne, wg kategorii) i widok szczegółów projektu.
 *
 * Metody: index(), archive(), category(), show().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ProjectController extends Controller
{
    /** Wyświetla listę aktywnych projektów pogrupowanych wg kategorii. */
    public function index()
    {
        $categories = Category::with(['publishedProjects' => fn ($query) => $query->where('is_completed', false)])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $hasArchive = Project::forCurrentSite()->where('is_published', true)->where('is_completed', true)->exists();

        if (SiteSetting::current()->site_template === 'federation') {
            return view('templates.federation.projects-index', compact('categories', 'hasArchive'));
        }

        // Szablon FEER ma własny układ listy projektów (nawigacja kategorii + wiersze).
        if (SiteSetting::current()->site_template === 'feer') {
            return view('projects.index-feer', compact('categories', 'hasArchive'));
        }

        return view('projects.index', compact('categories', 'hasArchive'));
    }

    /** Wyświetla archiwum zakończonych projektów. */
    public function archive(\Illuminate\Http\Request $request)
    {
        // Filtry okresu: ?przed=RRRR-MM-DD (zakończone wcześniej lub bez daty)
        // i ?po=RRRR-MM-DD (zakończone tego dnia lub później). Zły format = brak filtra.
        $parseDate = function (?string $value): ?\Carbon\Carbon {
            try {
                return filled($value) ? \Carbon\Carbon::createFromFormat('Y-m-d', $value)->startOfDay() : null;
            } catch (\Throwable) {
                return null;
            }
        };
        $before = $parseDate($request->query('przed'));
        $after  = $parseDate($request->query('po'));

        $projects = Project::forCurrentSite()->where('is_published', true)
            ->where('is_completed', true)
            ->when($before, fn ($q) => $q->where(fn ($w) => $w->whereDate('completed_at', '<', $before)->orWhereNull('completed_at')))
            ->when($after, fn ($q) => $q->whereDate('completed_at', '>=', $after))
            ->with('category')
            ->orderByDesc('completed_at')
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        $archiveFilter = match (true) {
            (bool) $before && (bool) $after => 'zrealizowane od ' . $after->translatedFormat('j F Y') . ' do ' . $before->translatedFormat('j F Y'),
            (bool) $before => 'zrealizowane przed ' . $before->translatedFormat('j F Y'),
            (bool) $after => 'zrealizowane od ' . $after->translatedFormat('j F Y'),
            default => null,
        };

        if (SiteSetting::current()->site_template === 'federation') {
            return view('templates.federation.projects-archive', compact('projects', 'archiveFilter'));
        }

        return view('projects.archive', compact('projects', 'archiveFilter'));
    }

    /** Wyświetla projekty należące do wybranej kategorii. */
    public function category(Category $category)
    {
        $category->load(['publishedProjects' => fn ($query) => $query->where('is_completed', false)]);

        if (SiteSetting::current()->site_template === 'federation') {
            return view('templates.federation.projects-category', compact('category'));
        }

        return view('projects.category', compact('category'));
    }

    /** Wyświetla stronę szczegółów opublikowanego projektu z kolorem akcentu dla grupy docelowej. */
    public function show(Project $project)
    {
        abort_unless($project->is_published, 404);
        $project->load('category', 'publishedPages', 'attachments');

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $project->accent_color ?: SiteSetting::current()->audienceColor($project->audience);

        if (SiteSetting::current()->site_template === 'federation') {
            return view('templates.federation.projects-show', compact('project', 'brandColor'));
        }

        return view('projects.show', compact('project', 'brandColor'));
    }
}
