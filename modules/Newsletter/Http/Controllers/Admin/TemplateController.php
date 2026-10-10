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
        return view('newsletter::admin.templates.index', [
            'templates' => NewsletterTemplate::whereNull('system_key')->orderByDesc('is_default')->orderBy('name')->get(),
            'systemMails' => collect(NewsletterTemplate::SYSTEM_MAILS)->map(fn ($def, $key) => $def + ['key' => $key, 'template' => NewsletterTemplate::system($key)]),
        ]);
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
        $wasSystem = $template->isSystem();
        $template->forceDelete();

        return redirect()->route('admin.newsletter.szablony.index')->with('status', $wasSystem ? 'Przywrócono wbudowany wygląd maila systemowego.' : 'Szablon usunięty.');
    }

    public function editor(NewsletterTemplate $template)
    {
        abort_unless($template->kind === 'mosaico', 404);
        $sys = $template->system_key ? (NewsletterTemplate::SYSTEM_MAILS[$template->system_key] ?? null) : null;

        return view('newsletter::admin.editor', [
            'subject'  => $template,
            'saveUrl'  => route('admin.newsletter.szablony.editor.save', $template),
            'backUrl'  => route('admin.newsletter.szablony.index'),
            'title'    => ($sys ? 'Mail systemowy: ' : 'Szablon: ') . $template->name,
            'mosaicoTemplate' => $template->mosaico_template ?: 'feer-1',
            'systemMail' => $sys,
            'mailSubject' => $template->subject ?: ($sys['subject'] ?? null),
        ]);
    }

    /** Otwiera (tworząc przy pierwszym wejściu) szablon systemowy w Mosaico. */
    public function system(string $key)
    {
        $def = NewsletterTemplate::SYSTEM_MAILS[$key] ?? abort(404);
        $template = NewsletterTemplate::system($key) ?? NewsletterTemplate::create([
            'site_id' => SiteSetting::current()->id, 'name' => $def['name'], 'subject' => $def['subject'], 'kind' => 'mosaico',
            'system_key' => $key, 'mosaico_template' => 'feer-1', 'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.newsletter.szablony.editor', $template);
    }

    public function saveEditor(Request $request, NewsletterTemplate $template)
    {
        $data = $request->validate(['metadata' => ['required', 'array'], 'content' => ['required', 'array'], 'html' => ['required', 'string'], 'subject' => ['nullable', 'string', 'max:255']]);
        $template->update(['editor_metadata' => $data['metadata'], 'editor_content' => $data['content'], 'html_body' => $data['html'], 'subject' => $data['subject'] ?? $template->subject]);

        $warnings = [];
        if ($template->system_key && ($req = NewsletterTemplate::SYSTEM_MAILS[$template->system_key]['required'] ?? null) && ! str_contains($data['html'], $req)) {
            $warnings[] = "Brak tagu {{{$req}}} — bez niego mail systemowy nie będzie używany (zostanie wysłany wbudowany).";
        }

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s'), 'warnings' => $warnings]);
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
