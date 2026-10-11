<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Legacy extends PageType
{
    public function key(): string
    {
        return 'legacy';
    }

    public function label(): string
    {
        return 'Prezentacja tego, co było';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 130;
    }

    public function groupOrder(): int
    {
        return 50;
    }

    public function icon(): ?string
    {
        return 'fa-clock-rotate-left';
    }
}
