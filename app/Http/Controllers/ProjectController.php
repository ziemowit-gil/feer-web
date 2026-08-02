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

        $hasArchive = Project::where('is_published', true)->where('is_completed', true)->exists();

        return view('projects.index', compact('categories', 'hasArchive'));
    }

    /** Wyświetla archiwum zakończonych projektów. */
    public function archive()
    {
        $projects = Project::where('is_published', true)
            ->where('is_completed', true)
            ->with('category')
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('projects.archive', compact('projects'));
    }

    /** Wyświetla projekty należące do wybranej kategorii. */
    public function category(Category $category)
    {
        $category->load(['publishedProjects' => fn ($query) => $query->where('is_completed', false)]);

        return view('projects.category', compact('category'));
    }

    /** Wyświetla stronę szczegółów opublikowanego projektu z kolorem akcentu dla grupy docelowej. */
    public function show(Project $project)
    {
        abort_unless($project->is_published, 404);
        $project->load('category', 'publishedPages');

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $project->accent_color ?: SiteSetting::current()->audienceColor($project->audience);

        return view('projects.show', compact('project', 'brandColor'));
    }
}
