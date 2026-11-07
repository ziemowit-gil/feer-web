<?php

declare(strict_types=1);

namespace Modules\Contact\PageTypes;

use App\PageTypes\PageType;

/**
 * Typ strony „Kontakt — książka”: książka telefoniczna jako podstrona kontaktu (kto, numer komórkowy, w jakiej sprawie
 * i kiedy dzwonić). Osoby są wspólne z modułem „Książka telefoniczna” (tabela phone_book_entries, panel: Komunikacja →
 * Książka telefoniczna), a na stronie ustawia się tylko wygląd: układ, kolejność, ukrywanie numerów i opcjonalnie dział.
 * Zastępuje dawny typ „Książka telefoniczna” (osoby w polach strony) — takie strony przenosi migracja modułu.
 */
final class ContactBookPageType extends PageType
{
    public function key(): string
    {
        return 'contact_book';
    }

    public function label(): string
    {
        return 'Kontakt — książka (kto, numer komórkowy i w jakiej sprawie dzwonić)';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 161;
    }

    public function groupOrder(): int
    {
        return 11;
    }

    public function icon(): ?string
    {
        return 'fa-address-book';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.layout' => ['nullable', 'in:table,cards'],
            'type_data.sort' => ['nullable', 'in:manual,alpha'],
            'type_data.hide_numbers' => ['nullable', 'boolean'],
            'type_data.group' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function frontView(): ?string
    {
        return 'contact-module::front.book';
    }

    public function adminView(): ?string
    {
        return 'contact-module::admin.book-fields';
    }
}
