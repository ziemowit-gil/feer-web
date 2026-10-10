<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GdprClause;
use App\Models\SiteSetting;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Newsletter\Models\NewsletterForm;
use Modules\Newsletter\Models\NewsletterList;

/** Panel: dedykowane formularze zapisu (treści, pola, tematy, zgoda, listy, SZO). */
class FormController extends Controller
{
    public function index()
    {
        return view('newsletter::admin.forms.index', ['forms' => NewsletterForm::orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function create()
    {
        $form = NewsletterForm::makeDefault();
        $form->is_default = ! NewsletterForm::exists();

        return $this->form($form);
    }

    public function store(Request $request)
    {
        $form = NewsletterForm::create($this->validated($request) + ['site_id' => SiteSetting::current()->id]);
        $this->ensureSingleDefault($form);

        return redirect()->route('admin.newsletter.formularze.index')->with('status', "Formularz „{$form->name}” utworzony.");
    }

    public function edit(NewsletterForm $form)
    {
        return $this->form($form);
    }

    public function update(Request $request, NewsletterForm $form)
    {
        $form->update($this->validated($request, $form));
        $this->ensureSingleDefault($form);

        return redirect()->route('admin.newsletter.formularze.edit', $form)->with('status', 'Formularz zapisany.');
    }

    public function destroy(NewsletterForm $form)
    {
        $form->delete();

        return redirect()->route('admin.newsletter.formularze.index')->with('status', 'Formularz usunięty.');
    }

    private function form(NewsletterForm $form)
    {
        $clauses = class_exists(GdprClause::class) && \Illuminate\Support\Facades\Schema::hasTable('gdpr_clauses')
            ? GdprClause::query()->orderBy('name')->get(['id', 'name']) : collect();

        return view('newsletter::admin.forms.form', [
            'form'    => $form,
            'topics'  => Subscriber::availableTopics(),
            'lists'   => NewsletterList::orderBy('name')->get(),
            'clauses' => $clauses,
            'styles'  => NewsletterForm::STYLES,
            'szoForms'=> $this->szoForms(),
        ]);
    }

    private function szoForms(): array
    {
        try {
            return collect(app(\App\Services\SzoClient::class)->forms())->mapWithKeys(fn ($f) => [($f['slug'] ?? '') => ($f['name'] ?? $f['slug'] ?? '')])->filter()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function validated(Request $request, ?NewsletterForm $form = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'heading' => ['required', 'string', 'max:200'],
            'lead' => ['nullable', 'string', 'max:1000'],
            'topics' => ['nullable', 'array'],
            'topics.*' => [Rule::in(array_keys(Subscriber::availableTopics()))],
            'default_topics' => ['nullable', 'array'],
            'default_topics.*' => [Rule::in(array_keys(Subscriber::availableTopics()))],
            'consent_text' => ['required', 'string', 'max:1000'],
            'clause_id' => ['nullable', 'integer'],
            'privacy_url' => ['nullable', 'string', 'max:500'],
            'button_label' => ['required', 'string', 'max:60'],
            'success_message' => ['required', 'string', 'max:1000'],
            'style' => ['required', Rule::in(array_keys(NewsletterForm::STYLES))],
            'accent_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'list_ids' => ['nullable', 'array'],
            'list_ids.*' => ['integer'],
            'szo_form_slug' => ['nullable', 'string', 'max:120'],
        ]);
        foreach (['ask_name', 'ask_phone', 'show_topics', 'offer_webpush', 'offer_sms', 'is_default', 'is_active'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']) ?: 'formularz';
        $base = $data['slug'];
        $i = 2;
        while (NewsletterForm::where('slug', $data['slug'])->when($form, fn ($q) => $q->where('id', '!=', $form->id))->exists()) {
            $data['slug'] = $base . '-' . $i++;
        }
        $data['topics'] = array_values($data['topics'] ?? []);
        $data['default_topics'] = array_values(array_intersect($data['default_topics'] ?? [], $data['topics'] ?: array_keys(Subscriber::availableTopics())));
        $data['list_ids'] = array_values(array_map('intval', $data['list_ids'] ?? []));

        return $data;
    }

    private function ensureSingleDefault(NewsletterForm $form): void
    {
        if ($form->is_default) {
            NewsletterForm::where('id', '!=', $form->id)->update(['is_default' => false]);
        } elseif (! NewsletterForm::where('is_default', true)->exists()) {
            $form->forceFill(['is_default' => true])->save();
        }
    }
}
