<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class LinksHub extends PageType
{
    public function key(): string
    {
        return 'links_hub';
    }

    public function label(): string
    {
        return 'Strona z kafelkami — metro (publiczne linki do działów)';
    }

    public function group(): string
    {
        return 'Kafelki i nawigacja';
    }

    public function order(): int
    {
        return 100;
    }

    public function groupOrder(): int
    {
        return 10;
    }

    public function icon(): ?string
    {
        return 'fa-table-cells-large';
    }
}
