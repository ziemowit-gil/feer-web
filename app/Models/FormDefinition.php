<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FormDefinition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'fields',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'fields'    => 'array',
        'settings'  => 'array',
        'is_active' => 'boolean',
    ];

    const FIELD_TYPES = [
        'text'     => 'Tekst (jednoliniowy)',
        'textarea' => 'Tekst (wieloliniowy)',
        'email'    => 'Adres e-mail',
        'tel'      => 'Numer telefonu',
        'number'   => 'Liczba',
        'select'   => 'Lista wyboru',
        'radio'    => 'Wybór jednokrotny (radio)',
        'checkbox' => 'Zgoda / checkbox',
        'date'     => 'Data',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function unreadSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->whereNull('read_at');
    }

    /** Normalizuje pola: generuje klucze ze słownikową de-duplikacją. */
    public function normalizedFields(): array
    {
        $seen   = [];
        $result = [];

        foreach ($this->fields ?? [] as $field) {
            $key = Str::slug($field['label'] ?? '', '_') ?: 'pole';

            if (isset($seen[$key])) {
                $seen[$key]++;
                $key .= '_' . $seen[$key];
            } else {
                $seen[$key] = 1;
            }

            $result[] = array_merge($field, ['key' => $key]);
        }

        return $result;
    }

    /** Dynamiczne reguły walidacji dla zgłoszenia. */
    public function validationRules(): array
    {
        $rules = [];

        foreach ($this->normalizedFields() as $field) {
            $required = ($field['required'] ?? false) ? 'required' : 'nullable';
            $type     = $field['type'] ?? 'text';

            $fieldRules = match ($type) {
                'email'    => [$required, 'email', 'max:255'],
                'tel'      => [$required, 'string', 'max:30'],
                'number'   => [$required, 'numeric'],
                'date'     => [$required, 'date'],
                'select',
                'radio'    => [$required, 'string', 'max:255'],
                'checkbox' => [$required, 'accepted'],
                default    => [$required, 'string', 'max:5000'],
            };

            $rules['data.' . $field['key']] = $fieldRules;
        }

        return $rules;
    }

    public function validationAttributes(): array
    {
        $attrs = [];

        foreach ($this->normalizedFields() as $field) {
            $attrs['data.' . $field['key']] = $field['label'] ?? $field['key'];
        }

        return $attrs;
    }
}
