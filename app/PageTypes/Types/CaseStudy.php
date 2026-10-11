<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class CaseStudy extends PageType
{
    public function key(): string
    {
        return 'case_study';
    }

    public function label(): string
    {
        return 'Studium przypadku (wyzwanie, rozwiązanie, efekty, cytat)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 200;
    }

    public function groupOrder(): int
    {
        return 30;
    }

    public function icon(): ?string
    {
        return 'fa-chart-line';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'cta') + [
            'type_data.client' => ['nullable', 'string', 'max:160'],
            'type_data.sector' => ['nullable', 'string', 'max:120'],
            'type_data.period' => ['nullable', 'string', 'max:80'],
            'type_data.challenge' => ['nullable', 'string', 'max:5000'],
            'type_data.solution' => ['nullable', 'string', 'max:5000'],
            'type_data.results' => ['nullable', 'array'],
            'type_data.results.*.value' => ['nullable', 'string', 'max:40'],
            'type_data.results.*.label' => ['nullable', 'string', 'max:120'],
            'type_data.quote' => ['nullable', 'string', 'max:1000'],
            'type_data.quote_author' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function listKeys(): array
    {
        return ['results'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.case-study';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.case_study';
    }
}
