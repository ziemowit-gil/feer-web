<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SklepCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buyer_email' => ['required', 'email', 'max:255'],
            'buyer_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'buyer_email' => 'adres e-mail',
            'buyer_name' => 'imię i nazwisko',
        ];
    }
}
