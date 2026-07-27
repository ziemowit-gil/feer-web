<?php

namespace App\Http\Requests;

use App\Models\VolunteerAd;
use App\Rules\BezOgolnikow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VolunteerAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dostęp pilnuje middleware trasy (module-access:volunteering)
    }

    /**
     * Pola listowe („Na czym polega" i „Co zyska wolontariusz") edytujemy jako
     * textarea — jedna pozycja w wierszu. Tu zamieniamy je na tablice, żeby
     * walidacja mogła wymusić co najmniej jedną konkretną pozycję.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'q_tasks' => $this->linesToArray($this->input('q_tasks')),
            'q_benefits' => $this->linesToArray($this->input('q_benefits')),
        ]);
    }

    private function linesToArray($value): array
    {
        if (is_array($value)) {
            $lines = $value;
        } else {
            $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
        }

        return array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'lead' => ['required', 'string', 'max:300', new BezOgolnikow],
            // 1. Komu pomagamy?
            'q_beneficiaries' => ['required', 'string', 'max:1000', new BezOgolnikow],
            // 2. Na czym polega? (min. 1 konkretne zadanie)
            'q_tasks' => ['required', 'array', 'min:1'],
            'q_tasks.*' => ['required', 'string', 'max:300'],
            // 3. Kiedy i gdzie?
            'q_mode' => ['required', Rule::in(array_keys(VolunteerAd::MODES))],
            'q_location' => ['nullable', 'required_unless:q_mode,zdalnie', 'string', 'max:200'],
            'q_schedule' => ['required', 'string', 'max:500'],
            // 4. Ile czasu?
            'q_time_commitment' => ['required', 'string', 'max:300'],
            // 5. Co zyska wolontariusz? (min. 1 korzyść)
            'q_benefits' => ['required', 'array', 'min:1'],
            'q_benefits.*' => ['required', 'string', 'max:300'],
            // 6. Jak się zgłosić? + osobny link do formularza
            'q_how_to_apply' => ['required', 'string', 'max:1000'],
            'application_url' => ['nullable', 'url', 'max:500'],
            'application_cta_label' => ['nullable', 'string', 'max:60'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'audience' => ['required', 'string', 'max:60'],
            'is_published' => ['sometimes', 'boolean'],
            'closes_at' => ['nullable', 'date'],
            'order' => ['nullable', 'integer', 'min:0'],
            'slug' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'q_tasks.required' => 'Podaj co najmniej jedno konkretne zadanie (pytanie 2: na czym polega wolontariat).',
            'q_tasks.min' => 'Podaj co najmniej jedno konkretne zadanie (pytanie 2: na czym polega wolontariat).',
            'q_benefits.required' => 'Podaj co najmniej jedną korzyść (pytanie 5: co zyska wolontariusz).',
            'q_benefits.min' => 'Podaj co najmniej jedną korzyść (pytanie 5: co zyska wolontariusz).',
            'q_location.required_unless' => 'Podaj lokalizację (chyba że wolontariat jest w pełni zdalny).',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tytuł',
            'lead' => 'krótki wstęp',
            'q_beneficiaries' => 'komu pomagamy',
            'q_schedule' => 'kiedy i gdzie',
            'q_time_commitment' => 'ile czasu',
            'q_how_to_apply' => 'jak się zgłosić',
        ];
    }
}
