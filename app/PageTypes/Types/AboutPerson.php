<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class AboutPerson extends PageType
{
    public function key(): string
    {
        return 'about_person';
    }

    public function label(): string
    {
        return 'O organizacji — osoba';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 150;
    }

    public function groupOrder(): int
    {
        return 20;
    }
}
