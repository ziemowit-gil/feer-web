<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel admin: zarządzanie kampaniami zbiórowymi.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy(), bulk().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class CampaignController extends Controller
{
    /** Lista kampanii z filtrowaniem po statusie. */
    public function index(Request $request)
    {
        $status = $request->query('status', '');

        $campaigns = Campaign::withTrashed()
            ->when($status === 'published', fn ($q) => $q->where('is_published', true)->whereNull('deleted_at'))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false)->whereNull('deleted_at'))
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->when($status === '', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.campaigns.index', [
            'campaigns' => $campaigns,
            'status'    => $status,
        ]);
    }

    public function create()
    {
        return view('admin.campaigns.form', ['campaign' => new Campaign]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $campaign = Campaign::create($data);

        if ($request->hasFile('image')) {
            $campaign->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.kampanie.index')->with('status', 'Kampania została utworzona.');
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.form', ['campaign' => $campaign]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $campaign->id);

        $campaign->update($data);

        if ($request->hasFile('image')) {
            $campaign->addMediaFromRequest('image')->toMediaCollection('image');
        }

        if ($request->boolean('remove_image')) {
            $campaign->clearMediaCollection('image');
        }

        return redirect()->route('admin.kampanie.index')->with('status', 'Kampania została zaktualizowana.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.kampanie.index')->with('status', 'Kampania przeniesiona do kosza.');
    }

    /** Akcje zbiorcze: publish, unpublish, trash, restore. */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,trash,restore'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
        ]);

        $campaigns = Campaign::withTrashed()->whereIn('id', $data['ids'])->get();

        if ($campaigns->isEmpty()) {
            return back()->with('error', 'Nie znaleziono kampanii.');
        }

        $count = $campaigns->count();

        match ($data['action']) {
            'trash'     => $campaigns->each->delete(),
            'restore'   => $campaigns->each->restore(),
            'publish'   => Campaign::whereIn('id', $campaigns->pluck('id'))->update(['is_published' => true]),
            'unpublish' => Campaign::whereIn('id', $campaigns->pluck('id'))->update(['is_published' => false]),
        };

        $message = match ($data['action']) {
            'trash'     => "Przeniesiono do kosza: {$count}.",
            'restore'   => "Przywrócono: {$count}.",
            'publish'   => "Opublikowano: {$count}.",
            'unpublish' => "Cofnięto publikację: {$count}.",
        };

        return back()->with('status', $message);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255'],
            'excerpt'          => ['nullable', 'string', 'max:400'],
            'content'          => ['nullable', 'string'],
            'goal_amount'      => ['required', 'integer', 'min:0'],
            'collected_amount' => ['required', 'integer', 'min:0'],
            'donation_url'     => ['nullable', 'url', 'max:500'],
            'starts_at'        => ['nullable', 'date'],
            'ends_at'          => ['nullable', 'date', 'after_or_equal:starts_at'],
            'order'            => ['nullable', 'integer', 'min:0'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'image'            => ['nullable', 'image', 'max:4096'],
        ]);

        $data['slug']  = trim($data['slug'] ?? '');
        $data['order'] = $data['order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');

        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'kampania';
        $slug = $base;
        $suffix = 2;

        while (Campaign::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
