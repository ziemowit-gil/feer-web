<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TrainingInstitution extends PageType
{
    public function key(): string
    {
        return 'training_institution';
    }

    public function label(): string
    {
        return 'Instytucja szkoleniowa';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 60;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-graduation-cap';
    }
}
