<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class BrandAssets extends PageType
{
    public function key(): string
    {
        return 'brand_assets';
    }

    public function label(): string
    {
        return 'Marka — identyfikacja wizualna (pliki do pobrania)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 140;
    }

    public function groupOrder(): int
    {
        return 60;
    }

    public function icon(): ?string
    {
        return 'fa-palette';
    }
}
