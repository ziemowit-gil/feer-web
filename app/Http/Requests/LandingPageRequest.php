<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dostęp pilnuje middleware trasy (module-access:landing)
    }

    public function rules(): array
    {
        $id = $this->route('landing')?->id;

        return [
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('landing_pages', 'slug')->ignore($id)],
            'title' => ['required', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],

            'hero_eyebrow' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_lead' => ['nullable', 'string', 'max:1000'],
            'hero_cta_label' => ['nullable', 'string', 'max:60'],
            'hero_image_url' => ['nullable', 'string', 'max:2048'],
            'event_start' => ['nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:150'],

            'speakers' => ['nullable', 'array'],
            'speakers.*.name' => ['nullable', 'string', 'max:150'],
            'speakers.*.role' => ['nullable', 'string', 'max:150'],
            'speakers.*.bio' => ['nullable', 'string', 'max:1000'],
            'speakers.*.photo' => ['nullable', 'string', 'max:2048'],

            'benefits' => ['nullable', 'array'],
            'benefits.*.icon' => ['nullable', 'string', 'max:60'],
            'benefits.*.title' => ['nullable', 'string', 'max:150'],
            'benefits.*.text' => ['nullable', 'string', 'max:500'],

            'agenda' => ['nullable', 'array'],
            'agenda.*.time' => ['nullable', 'string', 'max:40'],
            'agenda.*.title' => ['nullable', 'string', 'max:150'],
            'agenda.*.desc' => ['nullable', 'string', 'max:500'],

            'section_order' => ['nullable', 'array'],
            'section_order.*' => ['string', Rule::in(array_keys(\App\Models\LandingPage::SECTIONS))],

            'form_title' => ['nullable', 'string', 'max:150'],
            'form_intro' => ['nullable', 'string', 'max:500'],
            'form_success' => ['nullable', 'string', 'max:500'],
            'form_consent_label' => ['nullable', 'string', 'max:500'],
        ];
    }
}
