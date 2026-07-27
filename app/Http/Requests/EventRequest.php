<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dostęp pilnuje middleware trasy (module-access:events)
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'lead' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'facilitator_name' => ['nullable', 'string', 'max:160'],
            'facilitator_role' => ['nullable', 'string', 'max:160'],
            'facilitator_bio' => ['nullable', 'string', 'max:2000'],
            'facilitator_photo' => ['nullable', 'image', 'max:4096'],
            'remove_facilitator_photo' => ['sometimes', 'boolean'],
            'type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'mode' => ['required', Rule::in(array_keys(Event::MODES))],
            // Lokalizacja wymagana, gdy wydarzenie nie jest w pełni zdalne.
            'location' => ['nullable', 'required_unless:mode,zdalnie', 'string', 'max:255'],
            'online_url' => ['nullable', 'url', 'max:500'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_url' => ['nullable', 'url', 'max:500'],
            'registration_cta_label' => ['nullable', 'string', 'max:60'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'price_info' => ['nullable', 'string', 'max:100'],
            'audience' => ['required', 'string', 'max:60'],
            'is_published' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'slug' => ['nullable', 'string', 'max:200'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:255'],
            'faqs.*.answer' => ['nullable', 'string', 'max:2000'],
            'global_faqs' => ['nullable', 'array'],
            'global_faqs.*' => ['integer', 'exists:faqs,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'location.required_unless' => 'Podaj miejsce (chyba że wydarzenie jest w pełni zdalne).',
            'ends_at.after_or_equal' => 'Termin zakończenia nie może być wcześniejszy niż rozpoczęcia.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tytuł',
            'lead' => 'krótki opis',
            'starts_at' => 'termin rozpoczęcia',
            'ends_at' => 'termin zakończenia',
        ];
    }
}
