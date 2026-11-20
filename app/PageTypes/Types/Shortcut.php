<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;
use Illuminate\Validation\Rule;

/**
 * „Skrót” (doktyp shortcut w TYPO3): strona bez treści, która przenosi do pierwszej
 * podstrony, wskazanej strony albo strony nadrzędnej (302 — cel może się zmienić).
 * W menu prowadzi od razu pod adres celu (Page::navUrl(), Page::shortcutTarget()).
 */
class Shortcut extends PageType
{
    public function key(): string
    {
        return 'shortcut';
    }

    public function label(): string
    {
        return 'Skrót (przenosi do pierwszej podstrony albo wskazanej strony)';
    }

    public function group(): string
    {
        return 'Wewnętrzne i przekierowania';
    }

    public function order(): int
    {
        return 249;
    }

    public function groupOrder(): int
    {
        return 39;
    }

    public function icon(): ?string
    {
        return 'fa-diamond-turn-right';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_data.shortcut_mode' => ['nullable', 'in:first_child,page,parent'],
            'type_data.target_page_id' => [
                'nullable', 'integer', 'exists:pages,id',
                Rule::requiredIf(fn () => request('type') === 'shortcut' && request('type_data.shortcut_mode') === 'page'),
            ],
        ];
    }

    /** Widok tylko w podglądzie z panelu (na żywo adres przekierowuje). */
    public function frontView(): ?string
    {
        return 'page.partials.typed.shortcut';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.shortcut';
    }
}
