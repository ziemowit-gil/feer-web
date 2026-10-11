<?php

namespace App\PageTypes\Types;

use App\Models\Page;
use App\PageTypes\PageType;
use Illuminate\Validation\Rule;

class Tender extends PageType
{
    public function key(): string
    {
        return 'tender';
    }

    public function label(): string
    {
        return 'Zapytanie ofertowe (zasada konkurencyjności: termin ofert, warunki, kryteria, zmiany, wynik)';
    }

    public function group(): string
    {
        return 'Organizacja';
    }

    public function order(): int
    {
        return 290;
    }

    public function groupOrder(): int
    {
        return 90;
    }

    public function icon(): ?string
    {
        return 'fa-file-signature';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'requirements', 'docs', 'contact') + [
            'type_data.tender_status' => ['nullable', Rule::in(array_keys(Page::TENDER_STATUSES))],
            'type_data.tender_number' => ['nullable', 'string', 'max:80'],
            'type_data.tender_published_at' => ['nullable', 'date'],
            'type_data.tender_deadline' => ['nullable', 'date'],
            'type_data.tender_cpv' => ['nullable', 'string', 'max:300'],
            'type_data.tender_place' => ['nullable', 'string', 'max:200'],
            'type_data.tender_period' => ['nullable', 'string', 'max:200'],
            'type_data.tender_bk_url' => ['nullable', 'string', 'max:2048', 'regex:~^https?://~i'],
            'type_data.tender_submission' => ['nullable', 'string', 'max:3000'],
            'type_data.tender_exclusions' => ['nullable', 'string', 'max:5000'],
            'type_data.tender_result' => ['nullable', 'string', 'max:5000'],
            'type_data.tender_result_at' => ['nullable', 'date'],
            'type_data.criteria' => ['nullable', 'array', 'max:20'],
            'type_data.criteria.*.name' => ['nullable', 'string', 'max:200'],
            'type_data.criteria.*.weight' => ['nullable', 'string', 'max:20'],
            'type_data.criteria.*.text' => ['nullable', 'string', 'max:1000'],
            'type_data.notices' => ['nullable', 'array', 'max:50'],
            'type_data.notices.*.date' => ['nullable', 'date'],
            'type_data.notices.*.title' => ['nullable', 'string', 'max:200'],
            'type_data.notices.*.text' => ['nullable', 'string', 'max:5000'],
            'type_data.notices.*.url' => ['nullable', 'string', 'max:2048', 'regex:~^(https?://|/)~i'],
        ];
    }

    public function listKeys(): array
    {
        return ['requirements', 'criteria', 'docs', 'notices'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.tender';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.tender';
    }
}
