<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Event extends PageType
{
    public function key(): string
    {
        return 'event';
    }

    public function label(): string
    {
        return 'Wydarzenie';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 20;
    }

    public function icon(): ?string
    {
        return 'fa-calendar';
    }
}
