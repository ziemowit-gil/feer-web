<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BipDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('bipDocument')?->id;

        return [
            'title'          => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('bip_documents', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'category'       => ['required', 'string', Rule::in(array_keys(\App\Models\BipDocument::CATEGORIES))],
            'content'        => ['nullable', 'string'],
            'summary'        => ['nullable', 'string', 'max:1000'],
            'is_published'   => ['boolean'],
            'order'          => ['nullable', 'integer', 'min:0'],
            'files'          => ['nullable', 'array'],
            'files.*'        => ['file', 'mimes:pdf,doc,docx,odt,xls,xlsx,ods,csv,zip,jpg,jpeg,png,webp', 'max:10240'],
            'remove_files'   => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'    => 'tytuł',
            'slug'     => 'adres URL (slug)',
            'category' => 'kategoria',
            'summary'  => 'skrót',
        ];
    }
}
