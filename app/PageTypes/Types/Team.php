<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Team extends PageType
{
    public function key(): string
    {
        return 'team';
    }

    public function label(): string
    {
        return 'Zespół (osoby z rolą i krótkim opisem)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 220;
    }

    public function groupOrder(): int
    {
        return 30;
    }

    public function icon(): ?string
    {
        return 'fa-people-group';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.members' => ['nullable', 'array', 'max:60'],
            'type_data.members.*.name' => ['nullable', 'string', 'max:120'],
            'type_data.members.*.role' => ['nullable', 'string', 'max:160'],
            'type_data.members.*.text' => ['nullable', 'string', 'max:600'],
            'type_data.members.*.email' => ['nullable', 'email', 'max:190'],
        ];
    }

    public function listKeys(): array
    {
        return ['members'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.team';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.team';
    }
}
