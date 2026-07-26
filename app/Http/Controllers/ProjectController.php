<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;

class ProjectController extends Controller
{
    public function index()
    {
        $categories = Category::with(['publishedProjects' => fn ($query) => $query->where('is_completed', false)])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $hasArchive = Project::where('is_published', true)->where('is_completed', true)->exists();

        return view('projects.index', compact('categories', 'hasArchive'));
    }

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

    public function category(Category $category)
    {
        $category->load(['publishedProjects' => fn ($query) => $query->where('is_completed', false)]);

        return view('projects.category', compact('category'));
    }

    public function show(Project $project)
    {
        abort_unless($project->is_published, 404);
        $project->load('category', 'publishedPages');

        // Własny kolor akcentu ma priorytet; w przeciwnym razie preset grupy docelowej.
        $brandColor = $project->accent_color ?: SiteSetting::current()->audienceColor($project->audience);

        return view('projects.show', compact('project', 'brandColor'));
    }
}
