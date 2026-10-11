<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Faq extends PageType
{
    public function key(): string
    {
        return 'faq';
    }

    public function label(): string
    {
        return 'FAQ (pytania i odpowiedzi)';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 50;
    }

    public function groupOrder(): int
    {
        return 20;
    }

    public function icon(): ?string
    {
        return 'fa-circle-question';
    }
}
