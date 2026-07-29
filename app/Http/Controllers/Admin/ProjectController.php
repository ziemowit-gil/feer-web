<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Project;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $category = $request->query('category', '');
        $sort = $request->query('sort', 'default');

        $projects = Project::with('category')
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($category !== '', fn ($q) => $q->where('category_id', $category))
            ->when($sort === 'title_asc', fn ($q) => $q->orderBy('title'))
            ->when($sort === 'title_desc', fn ($q) => $q->orderByDesc('title'))
            ->when($sort === 'default', fn ($q) => $q->orderBy('order')->orderBy('title'))
            ->get();

        return view('admin.projects.index', [
            'projects' => $projects,
            'categories' => Category::orderBy('order')->orderBy('name')->get(),
            'status' => $status,
            'category' => $category,
            'sort' => $sort,
        ]);
    }

    public function create()
    {
        return view('admin.projects.form', [
            'project' => new Project,
            'categories' => Category::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        $project = Project::create($data);

        if ($request->hasFile('image')) {
            $project->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.projekty.index')->with('status', 'Projekt został utworzony.');
    }

    public function edit(Project $project)
    {
        return view('admin.projects.form', [
            'project' => $project,
            'categories' => Category::orderBy('order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title'], $project->id);

        $project->update($data);

        if ($request->hasFile('image')) {
            $project->addMediaFromRequest('image')->toMediaCollection('image');
        }

        return redirect()->route('admin.projekty.index')->with('status', 'Projekt został zaktualizowany.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('admin.projekty.index')->with('status', 'Projekt został usunięty.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'for_whom' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', Rule::in(array_keys(SiteSetting::current()->audienceOptions()))],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'since' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'why' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:4096'],
            'coordinator_name' => ['nullable', 'string', 'max:255'],
            'coordinator_email' => ['nullable', 'email', 'max:255'],
            'coordinator_phone' => ['nullable', 'string', 'max:50'],
            'legacy_url' => ['nullable', 'url', 'max:255'],
            'custom_section_title_1' => ['nullable', 'string', 'max:255'],
            'custom_section_content_1' => ['nullable', 'string'],
            'custom_section_title_2' => ['nullable', 'string', 'max:255'],
            'custom_section_content_2' => ['nullable', 'string'],
            'custom_section_title_3' => ['nullable', 'string', 'max:255'],
            'custom_section_content_3' => ['nullable', 'string'],
            'pricing' => ['nullable', 'array'],
            'pricing.*.item' => ['nullable', 'string', 'max:255'],
            'pricing.*.price' => ['nullable', 'string', 'max:100'],
            'pricing.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $data['slug'] = trim($data['slug'] ?? '');
        $data['order'] = $data['order'] ?? 0;
        $data['audience'] = $data['audience'] ?? 'brand';
        // Własny kolor akcentu pilnujemy pod kątem kontrastu WCAG (jak brand/NGO).
        $data['accent_color'] = filled($data['accent_color'] ?? null)
            ? SiteSetting::current()->contrastSafeColor($data['accent_color'])
            : null;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_completed'] = $request->boolean('is_completed');

        // Odpłatny + cennik: pomijamy puste wiersze; gdy nieodpłatny — czyścimy.
        $data['is_paid'] = $request->boolean('is_paid');
        if ($data['is_paid']) {
            $pricing = [];
            foreach ((array) $request->input('pricing', []) as $row) {
                $item = trim((string) ($row['item'] ?? ''));
                $price = trim((string) ($row['price'] ?? ''));
                $note = trim((string) ($row['note'] ?? ''));
                if ($item !== '' || $price !== '') {
                    $pricing[] = ['item' => $item, 'price' => $price, 'note' => $note];
                }
            }
            $data['pricing'] = $pricing ?: null;
        } else {
            $data['pricing'] = null;
        }
        $data['is_featured_contact'] = $request->boolean('is_featured_contact');
        $data['show_coordinator'] = $request->boolean('show_coordinator');
        $data['show_legacy_box'] = $request->boolean('show_legacy_box');
        $data['sections_as_tabs'] = $request->boolean('sections_as_tabs');

        // Build up to 3 custom sections from the paired title/content fields,
        // skipping any where both are empty.
        $sections = [];
        for ($i = 1; $i <= 3; $i++) {
            $title = trim((string) $request->input("custom_section_title_{$i}"));
            $content = (string) $request->input("custom_section_content_{$i}");
            if ($title !== '' || trim(strip_tags($content)) !== '') {
                $sections[] = [
                    'title' => $title,
                    'content' => $content,
                    'featured' => $request->boolean("custom_section_featured_{$i}"),
                ];
            }
            unset($data["custom_section_title_{$i}"], $data["custom_section_content_{$i}"]);
        }
        $data['custom_sections'] = $sections ?: null;

        unset($data['image']);

        return $data;
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'projekt';
        $slug = $base;
        $suffix = 2;

        while (Project::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
