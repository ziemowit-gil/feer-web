<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Glossary extends PageType
{
    public function key(): string
    {
        return 'glossary';
    }

    public function label(): string
    {
        return 'Słownik pojęć (hasła z definicjami i indeksem liter)';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 190;
    }

    public function groupOrder(): int
    {
        return 30;
    }

    public function icon(): ?string
    {
        return 'fa-book';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.terms' => ['nullable', 'array'],
            'type_data.terms.*.term' => ['nullable', 'string', 'max:120'],
            'type_data.terms.*.definition' => ['nullable', 'string', 'max:3000'],
            'type_data.terms.*.related' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function listKeys(): array
    {
        return ['terms'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.glossary';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.glossary';
    }
}
