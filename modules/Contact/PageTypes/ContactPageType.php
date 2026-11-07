<?php

declare(strict_types=1);

namespace Modules\Contact\PageTypes;

use App\PageTypes\Types\Contact;

/**
 * Typ strony „Kontakt” z modułu Kontakt. Klucz (`contact`) i układ strony zostają jak we wbudowanym typie, dzięki czemu
 * istniejące strony kontaktu działają bez przenoszenia; moduł zmienia opis typu, a w panelu dokłada kartę „Podstrony
 * kontaktu” (widok contact-module::admin.contact-subpages, dołączany z partiala typu w formularzu strony).
 */
final class ContactPageType extends Contact
{
    public function label(): string
    {
        return 'Kontakt (dane, formularz i podstrony: spotkania, przesyłki, rachunki, oddziały, książka)';
    }
}
