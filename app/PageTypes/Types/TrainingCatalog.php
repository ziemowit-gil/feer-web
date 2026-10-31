<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TrainingCatalog extends PageType
{
    public function key(): string
    {
        return 'training_catalog';
    }

    public function label(): string
    {
        return 'Katalog szkoleń (lista szkoleń z podstron, slider wyróżnionych)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 211;
    }

    public function groupOrder(): int
    {
        return 211;
    }

    public function icon(): ?string
    {
        return 'fa-graduation-cap';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.show_slider' => ['nullable', 'boolean'],
        ];
    }

    public function listKeys(): array
    {
        return [];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.training-catalog';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.training_catalog';
    }
}
