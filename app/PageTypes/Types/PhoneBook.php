<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class PhoneBook extends PageType
{
    public function key(): string
    {
        return 'phone_book';
    }

    public function label(): string
    {
        return 'Książka telefoniczna (kto, numer komórkowy i w jakiej sprawie się kontaktować)';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    /** Wycofany: zastąpił go typ „Kontakt — książka” z modułu Kontakt (strony tego typu są przenoszone migracją). */
    public function selectable(): bool
    {
        return false;
    }

    public function order(): int
    {
        return 170;
    }

    public function groupOrder(): int
    {
        return 17;
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
            'type_data.contacts' => ['nullable', 'array', 'max:200'],
            'type_data.contacts.*.group' => ['nullable', 'string', 'max:120'],
            'type_data.contacts.*.name' => ['nullable', 'string', 'max:120'],
            'type_data.contacts.*.role' => ['nullable', 'string', 'max:160'],
            'type_data.contacts.*.phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-.]*$/'],
            'type_data.contacts.*.email' => ['nullable', 'email', 'max:190'],
            'type_data.contacts.*.topic' => ['nullable', 'string', 'max:500'],
            'type_data.contacts.*.hours' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function listKeys(): array
    {
        return ['contacts'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.phone-book';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.phone-book';
    }
}
