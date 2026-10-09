<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Newsletter\Models\NewsletterList;

class ListController extends Controller
{
    public function index()
    {
        return view('newsletter::admin.lists.index', ['lists' => NewsletterList::withCount('subscribers')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('newsletter::admin.lists.form', ['list' => new NewsletterList()]);
    }

    public function store(Request $request)
    {
        $list = NewsletterList::create($this->validated($request) + ['site_id' => SiteSetting::current()->id]);

        return redirect()->route('admin.newsletter.listy.index')->with('status', "Lista „{$list->name}” utworzona.");
    }

    public function edit(NewsletterList $list)
    {
        return view('newsletter::admin.lists.form', ['list' => $list]);
    }

    public function update(Request $request, NewsletterList $list)
    {
        $list->update($this->validated($request, $list));

        return redirect()->route('admin.newsletter.listy.index')->with('status', 'Lista zapisana.');
    }

    public function destroy(NewsletterList $list)
    {
        $list->delete();

        return redirect()->route('admin.newsletter.listy.index')->with('status', 'Lista usunięta (subskrybenci pozostają w bazie).');
    }

    private function validated(Request $request, ?NewsletterList $list = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']) ?: 'lista';
        $data['is_public'] = $request->boolean('is_public');
        $base = $data['slug'];
        $i = 2;
        while (NewsletterList::where('slug', $data['slug'])->when($list, fn ($q) => $q->where('id', '!=', $list->id))->exists()) {
            $data['slug'] = $base . '-' . $i++;
        }

        return $data;
    }
}
