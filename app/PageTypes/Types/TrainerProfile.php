<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TrainerProfile extends PageType
{
    public function key(): string
    {
        return 'trainer_profile';
    }

    public function label(): string
    {
        return 'Trener (specjalizacje, kwalifikacje, prowadzone szkolenia)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 212;
    }

    public function groupOrder(): int
    {
        return 212;
    }

    public function icon(): ?string
    {
        return 'fa-user-tie';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'contact') + [
            'type_data.role' => ['nullable', 'string', 'max:160'],
            'type_data.languages' => ['nullable', 'string', 'max:160'],
            'type_data.specializations' => ['nullable', 'array'],
            'type_data.specializations.*.text' => ['nullable', 'string', 'max:200'],
            'type_data.qualifications' => ['nullable', 'array'],
            'type_data.qualifications.*.text' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function listKeys(): array
    {
        return ['specializations', 'qualifications'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.trainer-profile';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.trainer_profile';
    }
}
