<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Reports extends PageType
{
    public function key(): string
    {
        return 'reports';
    }

    public function label(): string
    {
        return 'Sprawozdania roczne (tabela z modułu Sprawozdania — np. do BIP)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 260;
    }

    public function groupOrder(): int
    {
        return 70;
    }

    public function icon(): ?string
    {
        return 'fa-file-invoice';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead');
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.reports';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.reports';
    }
}
