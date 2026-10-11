<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Redirect extends PageType
{
    public function key(): string
    {
        return 'redirect';
    }

    public function label(): string
    {
        return 'Przekierowanie (strona przenosi na inny adres)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 250;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-share';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_data.target_url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/(?!/))~i'],
            'type_data.redirect_mode' => ['nullable', 'in:notice,auto'],
            'type_data.redirect_message' => ['nullable', 'string', 'max:600'],
            'type_data.redirect_label' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.redirect';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.redirect';
    }
}
