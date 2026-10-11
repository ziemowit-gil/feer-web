<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class BipMove extends PageType
{
    public function key(): string
    {
        return 'bip_move';
    }

    public function label(): string
    {
        return 'Przeniesiono do BIP';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 70;
    }

    public function groupOrder(): int
    {
        return 30;
    }

    public function icon(): ?string
    {
        return 'fa-landmark';
    }
}
