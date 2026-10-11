<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Wspolpraca extends PageType
{
    public function key(): string
    {
        return 'wspolpraca';
    }

    public function label(): string
    {
        return 'Współpraca z FEER (partnerstwo, sektory, formy, CTA)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 120;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-handshake';
    }
}
