<?php

namespace App\Http\Requests;

use App\Models\LandingPage;
use Illuminate\Foundation\Http\FormRequest;

class WebinarRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'consent' => ['accepted'],
            'extra' => ['nullable', 'array'],
        ];

        // Reguły dla dodatkowych pól zdefiniowanych w panelu dla tej strony.
        foreach ($this->page()?->formFields() ?? [] as $field) {
            $key = 'extra.'.$field['key'];
            $required = (bool) ($field['required'] ?? false);

            $rule = [$required ? ($field['type'] === 'checkbox' ? 'accepted' : 'required') : 'nullable'];

            if ($field['type'] === 'email') {
                $rule[] = 'email';
            } elseif ($field['type'] === 'select') {
                $rule[] = 'string';
            } else {
                $rule[] = 'string';
            }

            $rule[] = 'max:1000';
            $rules[$key] = $rule;
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attrs = ['name' => 'imię i nazwisko', 'email' => 'adres e-mail', 'consent' => 'zgoda'];

        foreach ($this->page()?->formFields() ?? [] as $field) {
            $attrs['extra.'.$field['key']] = mb_strtolower($field['label']);
        }

        return $attrs;
    }

    private function page(): ?LandingPage
    {
        return LandingPage::published()->where('slug', $this->route('slug'))->first();
    }
}
