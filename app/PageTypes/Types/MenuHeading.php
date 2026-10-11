<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

/**
 * Nagłówek w menu: niklikalny tytuł grupujący podstrony, które się pod nim
 * znajdują. Nie ma własnej treści — wejście na jego adres przenosi do pierwszej
 * opublikowanej podstrony (albo do strony nadrzędnej).
 */
class MenuHeading extends PageType
{
    public function key(): string
    {
        return 'menu_heading';
    }

    public function label(): string
    {
        return 'Nagłówek w menu (grupuje podstrony, bez własnej treści)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 252;
    }

    public function groupOrder(): int
    {
        return 40;
    }

    public function icon(): ?string
    {
        return 'fa-heading';
    }
}
