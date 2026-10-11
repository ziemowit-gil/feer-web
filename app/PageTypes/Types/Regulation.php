<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Regulation extends PageType
{
    public function key(): string
    {
        return 'regulation';
    }

    public function label(): string
    {
        return 'Regulamin lub dokument (data obowiązywania, spis treści)';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 240;
    }

    public function groupOrder(): int
    {
        return 60;
    }

    public function icon(): ?string
    {
        return 'fa-scale-balanced';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.valid_from' => ['nullable', 'string', 'max:60'],
            'type_data.version' => ['nullable', 'string', 'max:40'],
            'type_data.versions' => ['nullable', 'array', 'max:30'],
            'type_data.versions.*.label' => ['nullable', 'string', 'max:120'],
            'type_data.versions.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
        ];
    }

    public function listKeys(): array
    {
        return ['versions'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.regulation';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.regulation';
    }
}
