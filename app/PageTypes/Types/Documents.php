<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class Documents extends PageType
{
    public function key(): string
    {
        return 'documents';
    }

    public function label(): string
    {
        return 'Dokumenty (grupy odnośników do plików i stron)';
    }

    public function group(): string
    {
        return 'Treść i informacja';
    }

    public function order(): int
    {
        return 230;
    }

    public function groupOrder(): int
    {
        return 50;
    }

    public function icon(): ?string
    {
        return 'fa-folder-open';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'docs');
    }

    public function listKeys(): array
    {
        return ['docs'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.documents';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.documents';
    }
}
