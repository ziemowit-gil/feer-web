<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Pricing extends PageType
{
    public function key(): string
    {
        return 'pricing';
    }

    public function label(): string
    {
        return 'Cennik i opłaty (karty cen, zasady płatności)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 210;
    }

    public function groupOrder(): int
    {
        return 20;
    }

    public function icon(): ?string
    {
        return 'fa-coins';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'cta') + [
            'type_data.price_rows' => ['nullable', 'array', 'max:60'],
            'type_data.price_rows.*.group' => ['nullable', 'string', 'max:120'],
            'type_data.price_rows.*.item' => ['nullable', 'string', 'max:200'],
            'type_data.price_rows.*.price' => ['nullable', 'string', 'max:80'],
            'type_data.price_rows.*.note' => ['nullable', 'string', 'max:300'],
            'type_data.payment_info' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function listKeys(): array
    {
        return ['price_rows'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.pricing';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.pricing';
    }
}
