<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacilitatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:160'],
            'role'      => ['nullable', 'string', 'max:160'],
            'bio'       => ['nullable', 'string', 'max:2000'],
            'website'   => ['nullable', 'url', 'max:500'],
            'linkedin'  => ['nullable', 'url', 'max:500'],
            'facebook'  => ['nullable', 'url', 'max:500'],
            'instagram' => ['nullable', 'url', 'max:500'],
            'twitter'   => ['nullable', 'url', 'max:500'],
            'photo'     => ['nullable', 'image', 'max:4096'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }
}
