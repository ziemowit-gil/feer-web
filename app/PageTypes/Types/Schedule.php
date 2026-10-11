<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Schedule extends PageType
{
    public function key(): string
    {
        return 'schedule';
    }

    public function label(): string
    {
        return 'Harmonogram zajęć / spotkań';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 30;
    }

    public function icon(): ?string
    {
        return 'fa-calendar-days';
    }
}
