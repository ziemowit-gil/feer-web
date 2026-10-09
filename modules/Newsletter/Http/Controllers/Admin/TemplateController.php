<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Models\NewsletterTemplate;

class TemplateController extends Controller
{
    public function index()
    {
        return view('newsletter::admin.templates.index', ['templates' => NewsletterTemplate::orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('newsletter::admin.templates.form', ['template' => new NewsletterTemplate(['kind' => 'mosaico', 'mosaico_template' => 'feer-1'])]);
    }

    public function store(Request $request)
    {
        $t = NewsletterTemplate::create($this->validated($request) + ['site_id' => SiteSetting::current()->id, 'created_by' => auth()->id()]);
        if ($t->is_default) {
            NewsletterTemplate::where('id', '!=', $t->id)->update(['is_default' => false]);
        }

        return $t->kind === 'mosaico'
            ? redirect()->route('admin.newsletter.szablony.editor', $t)
            : redirect()->route('admin.newsletter.szablony.index')->with('status', 'Szablon utworzony.');
    }

    public function edit(NewsletterTemplate $template)
    {
        return view('newsletter::admin.templates.form', ['template' => $template]);
    }

    public function update(Request $request, NewsletterTemplate $template)
    {
        $template->update($this->validated($request));
        if ($template->is_default) {
            NewsletterTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        }

        return redirect()->route('admin.newsletter.szablony.index')->with('status', 'Szablon zapisany.');
    }

    public function destroy(NewsletterTemplate $template)
    {
        $template->delete();

        return redirect()->route('admin.newsletter.szablony.index')->with('status', 'Szablon usunięty.');
    }

    public function editor(NewsletterTemplate $template)
    {
        abort_unless($template->kind === 'mosaico', 404);

        return view('newsletter::admin.editor', [
            'subject'  => $template,
            'saveUrl'  => route('admin.newsletter.szablony.editor.save', $template),
            'backUrl'  => route('admin.newsletter.szablony.index'),
            'title'    => 'Szablon: ' . $template->name,
            'mosaicoTemplate' => $template->mosaico_template ?: 'feer-1',
        ]);
    }

    public function saveEditor(Request $request, NewsletterTemplate $template)
    {
        $data = $request->validate(['metadata' => ['required', 'array'], 'content' => ['required', 'array'], 'html' => ['required', 'string']]);
        $template->update(['editor_metadata' => $data['metadata'], 'editor_content' => $data['content'], 'html_body' => $data['html']]);

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    public function duplicate(NewsletterTemplate $template)
    {
        $copy = $template->replicate(['is_default']);
        $copy->name = $template->name . ' (kopia)';
        $copy->is_default = false;
        $copy->save();

        return redirect()->route('admin.newsletter.szablony.index')->with('status', 'Szablon zduplikowany.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(NewsletterTemplate::KINDS))],
            'mosaico_template' => ['nullable', 'string', 'max:64'],
            'html_body' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['is_default'] = $request->boolean('is_default');
        if ($data['kind'] === 'mosaico') {
            $data['mosaico_template'] = $data['mosaico_template'] ?: 'feer-1';
        }

        return $data;
    }
}
