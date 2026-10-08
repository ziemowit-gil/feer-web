<?php

namespace App\Http\Requests;

use App\Models\Authorization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Walidacja formularza wpisu rejestru pełnomocnictw i upoważnień. */
class AuthorizationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type'            => ['required', Rule::in(array_keys(Authorization::TYPES))],
            'document_number' => [
                'required', 'string', 'max:100',
                Rule::unique('authorizations', 'document_number')->ignore($this->route('pelnomocnictwo')),
            ],
            'principal'    => ['required', 'string', 'max:255'],
            'grantee_name' => ['required', 'string', 'max:255'],
            'scope'        => ['required', 'string', 'max:2000'],
            'valid_from'   => ['required', 'date'],
            'valid_to'     => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active'    => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function attributes(): array
    {
        return [
            'type'            => 'rodzaj dokumentu',
            'document_number' => 'numer dokumentu',
            'principal'       => 'udzielający',
            'grantee_name'    => 'osoba/podmiot umocowany',
            'scope'           => 'zakres umocowania',
            'valid_from'      => 'data od',
            'valid_to'        => 'data do',
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Dokument o tym numerze już istnieje w rejestrze.',
            'valid_to.after_or_equal' => 'Data „do" nie może być wcześniejsza niż data „od".',
        ];
    }
}
