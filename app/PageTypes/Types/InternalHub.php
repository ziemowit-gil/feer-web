<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class InternalHub extends PageType
{
    public function key(): string
    {
        return 'internal_hub';
    }

    public function label(): string
    {
        return 'Strefa współpracownika (wewnętrzny panel: komunikaty i odnośniki)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 90;
    }

    public function groupOrder(): int
    {
        return 20;
    }

    public function icon(): ?string
    {
        return 'fa-user-lock';
    }
}
