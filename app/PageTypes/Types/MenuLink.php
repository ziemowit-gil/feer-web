<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

/**
 * Podstrona będąca tylko pozycją menu z własnym adresem: w menu (boczne, drzewo
 * działu, rozwijane) prowadzi od razu pod adres docelowy, a wejście na jej własny
 * adres przekierowuje tam 301. Patrz Page::navUrl().
 */
class MenuLink extends PageType
{
    public function key(): string
    {
        return 'menu_link';
    }

    public function label(): string
    {
        return 'Własny link w menu (pozycja prowadzi pod wskazany adres)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 251;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-link';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_data.target_url' => ['required_if:type,menu_link', 'nullable', 'string', 'max:2048', 'regex:~^(https?://|/(?!/))~i'],
            'type_data.new_tab' => ['nullable', 'boolean'],
        ];
    }

    /** Widok tylko w podglądzie z panelu (na żywo adres przekierowuje) — ten sam co „Przekierowanie”. */
    public function frontView(): ?string
    {
        return 'page.partials.typed.redirect';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.menu-link';
    }
}
