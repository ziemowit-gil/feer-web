<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Contact extends PageType
{
    public function key(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return 'Kontakt (formularz, dane teleadresowe, spotkania)';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 160;
    }

    public function groupOrder(): int
    {
        return 10;
    }

    public function icon(): ?string
    {
        return 'fa-envelope';
    }
}
