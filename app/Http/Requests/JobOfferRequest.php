<?php

namespace App\Http\Requests;

use App\Models\JobOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'duties'        => $this->linesToArray($this->input('duties')),
            'requirements'  => $this->linesToArray($this->input('requirements')),
            'nice_to_have'  => $this->linesToArray($this->input('nice_to_have')),
            'benefits'      => $this->linesToArray($this->input('benefits')),
        ]);
    }

    private function linesToArray($value): array
    {
        if (is_array($value)) {
            $lines = $value;
        } else {
            $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
        }

        return array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
    }

    public function rules(): array
    {
        return [
            'title'                  => ['required', 'string', 'max:160'],
            'lead'                   => ['required', 'string', 'max:300'],
            'job_type'               => ['required', Rule::in(array_keys(JobOffer::TYPES))],
            'mode'                   => ['required', Rule::in(array_keys(JobOffer::MODES))],
            'location'               => ['nullable', 'required_unless:mode,zdalnie', 'string', 'max:200'],
            'salary_range'           => ['nullable', 'string', 'max:100'],
            'hourly_rate'            => ['nullable', 'string', 'max:100'],
            'contract_duration_type' => ['nullable', Rule::in(array_keys(JobOffer::CONTRACT_DURATION_TYPES))],
            'contract_duration'      => ['nullable', 'string', 'max:100'],
            'start_date'             => ['nullable', 'date'],
            'duties'                 => ['required', 'array', 'min:1'],
            'duties.*'               => ['required', 'string', 'max:300'],
            'requirements'           => ['required', 'array', 'min:1'],
            'requirements.*'         => ['required', 'string', 'max:300'],
            'nice_to_have'           => ['nullable', 'array'],
            'nice_to_have.*'         => ['required', 'string', 'max:300'],
            'benefits'               => ['nullable', 'array'],
            'benefits.*'             => ['required', 'string', 'max:300'],
            'contact_name'           => ['nullable', 'string', 'max:255'],
            'contact_email'          => ['nullable', 'email', 'max:255'],
            'application_url'        => ['nullable', 'url', 'max:500'],
            'application_cta_label'  => ['nullable', 'string', 'max:60'],
            'apply_note'             => ['nullable', 'string', 'max:1000'],
            'grant_condition'        => ['sometimes', 'boolean'],
            'offer_deadline'         => ['nullable', 'date'],
            'task_period'            => ['nullable', 'string', 'max:255'],
            'audience'               => ['required', 'string', 'max:60'],
            'is_published'           => ['sometimes', 'boolean'],
            'closes_at'              => ['nullable', 'date'],
            'order'                  => ['nullable', 'integer', 'min:0'],
            'slug'                   => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'duties.required'        => 'Podaj co najmniej jeden obowiązek.',
            'duties.min'             => 'Podaj co najmniej jeden obowiązek.',
            'requirements.required'  => 'Podaj co najmniej jedno wymaganie.',
            'requirements.min'       => 'Podaj co najmniej jedno wymaganie.',
            'location.required_unless' => 'Podaj lokalizację (chyba że stanowisko jest w pełni zdalne).',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'       => 'tytuł',
            'lead'        => 'krótki opis',
            'job_type'    => 'rodzaj umowy',
            'mode'        => 'tryb pracy',
            'duties'      => 'obowiązki',
            'requirements' => 'wymagania',
        ];
    }
}
