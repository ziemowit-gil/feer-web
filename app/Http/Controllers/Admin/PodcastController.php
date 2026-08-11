<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PodcastController extends Controller
{
    public function index(Request $request): View
    {
        $podcasts = Podcast::withTrashed()
            ->when($request->q, fn ($q) => $q->where('title', 'like', '%' . $request->q . '%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.podcasts.index', compact('podcasts'));
    }

    public function create(): View
    {
        return view('admin.podcasts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:podcasts,slug',
            'description' => 'nullable|string',
            'episode_number' => 'nullable|string|max:32',
            'is_premium' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'audio' => 'nullable|file|mimes:mp3,mp4,ogg,wav,m4a|max:204800',
            'cover' => 'nullable|image|max:4096',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['is_premium'] = $request->boolean('is_premium');
        $data['is_published'] = $request->boolean('is_published');

        $podcast = Podcast::create($data);

        if ($request->hasFile('audio')) {
            $podcast->addMediaFromRequest('audio')->toMediaCollection('audio');
        }

        if ($request->hasFile('cover')) {
            $podcast->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        return redirect()->route('admin.podcasts.index')
            ->with('success', 'Podcast „' . $podcast->title . '" został dodany.');
    }

    public function edit(Podcast $podcast): View
    {
        return view('admin.podcasts.edit', compact('podcast'));
    }

    public function update(Request $request, Podcast $podcast): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:podcasts,slug,' . $podcast->id,
            'description' => 'nullable|string',
            'episode_number' => 'nullable|string|max:32',
            'is_premium' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'audio' => 'nullable|file|mimes:mp3,mp4,ogg,wav,m4a|max:204800',
            'cover' => 'nullable|image|max:4096',
        ]);

        $data['is_premium'] = $request->boolean('is_premium');
        $data['is_published'] = $request->boolean('is_published');

        $podcast->update($data);

        if ($request->hasFile('audio')) {
            $podcast->addMediaFromRequest('audio')->toMediaCollection('audio');
        }

        if ($request->hasFile('cover')) {
            $podcast->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        return redirect()->route('admin.podcasts.index')
            ->with('success', 'Podcast „' . $podcast->title . '" został zaktualizowany.');
    }

    public function destroy(Podcast $podcast): RedirectResponse
    {
        $podcast->delete();

        return redirect()->route('admin.podcasts.index')
            ->with('success', 'Podcast usunięty.');
    }
}
