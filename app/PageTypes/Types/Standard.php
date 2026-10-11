<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Standard extends PageType
{
    public function key(): string
    {
        return 'standard';
    }

    public function label(): string
    {
        return 'Standardowa';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 10;
    }

    public function standardLayout(): bool
    {
        return true;
    }
}
