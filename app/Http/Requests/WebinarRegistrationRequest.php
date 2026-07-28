<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebinarRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'consent' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'imię i nazwisko', 'email' => 'adres e-mail', 'consent' => 'zgoda'];
    }
}
