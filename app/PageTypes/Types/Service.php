<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Service extends PageType
{
    public function key(): string
    {
        return 'service';
    }

    public function label(): string
    {
        return 'Oferta / usługa (korzyści, dla kogo, jak działamy, CTA)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 170;
    }

    public function groupOrder(): int
    {
        return 10;
    }

    public function icon(): ?string
    {
        return 'fa-briefcase';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'audience', 'benefits', 'steps', 'cta', 'contact');
    }

    public function listKeys(): array
    {
        return ['audience', 'benefits', 'steps'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.service';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.service';
    }
}
