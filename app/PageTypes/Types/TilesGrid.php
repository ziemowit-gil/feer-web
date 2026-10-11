<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TilesGrid extends PageType
{
    public function key(): string
    {
        return 'tiles_grid';
    }

    public function label(): string
    {
        return 'Siatka kafelków + treść (kafelki z ikonami, treść nad lub pod nimi, boczne menu)';
    }

    public function group(): string
    {
        return 'Kafelki i nawigacja';
    }

    public function order(): int
    {
        return 110;
    }

    public function groupOrder(): int
    {
        return 20;
    }

    public function icon(): ?string
    {
        return 'fa-table-cells';
    }
}
