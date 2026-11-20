<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

/**
 * „Folder systemowy” (sysfolder w TYPO3): porządkuje drzewo stron w panelu (np. strony kampanii,
 * archiwum, materiały pomocnicze). Na stronie publicznej go nie ma — adres daje 404, nie trafia
 * do menu, mapy strony, list podstron ani wyszukiwarki. Podstrony w środku działają pod swoimi adresami.
 * Podgląd z panelu pokazuje informację (widok page.sys-folder, PageController::show).
 */
class SysFolder extends PageType
{
    public function key(): string
    {
        return 'sys_folder';
    }

    public function label(): string
    {
        return 'Folder systemowy (porządkuje strony w panelu, niewidoczny na stronie)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 253;
    }

    public function groupOrder(): int
    {
        return 41;
    }

    public function icon(): ?string
    {
        return 'fa-box-archive';
    }
}
