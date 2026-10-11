<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

/**
 * „Formularz” — strona z wstępem (treść strony) i formularzem z modułu Formularze.
 * Obok: czas wypełnienia, termin i limit zgłoszeń (z ustawień formularza), co przygotować,
 * co dzieje się po wysłaniu i kontakt.
 */
class FormPage extends PageType
{
    public function key(): string
    {
        return 'form_page';
    }

    public function label(): string
    {
        return 'Formularz (wstęp, formularz, co przygotować, co dalej)';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 165;
    }

    public function groupOrder(): int
    {
        return 15;
    }

    public function icon(): ?string
    {
        return 'fa-pen-to-square';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'requirements', 'contact') + [
            'type_data.fp_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_-]+$/i'],
            'type_data.fp_heading' => ['nullable', 'string', 'max:160'],
            'type_data.fp_time' => ['nullable', 'string', 'max:80'],
            'type_data.fp_after' => ['nullable', 'string', 'max:3000'],
            'type_data.fp_help' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function listKeys(): array
    {
        return ['requirements'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.form-page';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.form-page';
    }
}
