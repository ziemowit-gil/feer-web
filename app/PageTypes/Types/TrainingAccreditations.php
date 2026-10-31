<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TrainingAccreditations extends PageType
{
    public function key(): string
    {
        return 'training_accreditations';
    }

    public function label(): string
    {
        return 'Akredytacje i jakość kształcenia (wpisy, certyfikaty, wyniki ankiet)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 213;
    }

    public function groupOrder(): int
    {
        return 213;
    }

    public function icon(): ?string
    {
        return 'fa-award';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.accreditations' => ['nullable', 'array'],
            'type_data.accreditations.*.name' => ['nullable', 'string', 'max:200'],
            'type_data.accreditations.*.issuer' => ['nullable', 'string', 'max:200'],
            'type_data.accreditations.*.number' => ['nullable', 'string', 'max:80'],
            'type_data.accreditations.*.valid_until' => ['nullable', 'date'],
            'type_data.accreditations.*.url' => ['nullable', 'string', 'max:300'],
            'type_data.quality_stats' => ['nullable', 'array'],
            'type_data.quality_stats.*.value' => ['nullable', 'string', 'max:40'],
            'type_data.quality_stats.*.label' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function listKeys(): array
    {
        return ['accreditations', 'quality_stats'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.training-accreditations';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.training_accreditations';
    }
}
