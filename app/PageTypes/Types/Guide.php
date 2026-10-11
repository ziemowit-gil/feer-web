<?php

namespace App\PageTypes\Types;

use App\Models\Page;
use App\PageTypes\PageType;
use Illuminate\Validation\Rule;

class Guide extends PageType
{
    public function key(): string
    {
        return 'guide';
    }

    public function label(): string
    {
        return 'Poradnik krok po kroku (numerowane kroki, wymagania, podsumowanie)';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 180;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-list-ol';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'requirements', 'steps') + [
            'type_data.time_estimate' => ['nullable', 'string', 'max:60'],
            'type_data.level' => ['nullable', Rule::in(array_keys(Page::GUIDE_LEVELS))],
            'type_data.summary' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function listKeys(): array
    {
        return ['requirements', 'steps'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.guide';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.guide';
    }
}
