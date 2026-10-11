<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Internal extends PageType
{
    public function key(): string
    {
        return 'internal';
    }

    public function label(): string
    {
        return 'Wewnętrzna (dostęp ograniczony)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 80;
    }

    public function groupOrder(): int
    {
        return 10;
    }

    public function icon(): ?string
    {
        return 'fa-lock';
    }

    public function standardLayout(): bool
    {
        return true;
    }
}
