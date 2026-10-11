<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class About extends PageType
{
    public function key(): string
    {
        return 'about';
    }

    public function label(): string
    {
        return 'O organizacji';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 40;
    }

    public function groupOrder(): int
    {
        return 10;
    }

    public function icon(): ?string
    {
        return 'fa-building';
    }
}
