<?php

namespace App\PageTypes\Types;

use App\Models\Page;
use App\PageTypes\PageType;
use Illuminate\Validation\Rule;

class Recruitment extends PageType
{
    public function key(): string
    {
        return 'recruitment';
    }

    public function label(): string
    {
        return 'Rekrutacja do projektu (status naboru, terminy, kryteria, dokumenty, zgłoszenie)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 280;
    }

    public function groupOrder(): int
    {
        return 50;
    }

    public function icon(): ?string
    {
        return 'fa-user-plus';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'audience', 'requirements', 'benefits', 'steps', 'docs', 'cta', 'contact') + [
            'type_data.rec_status' => ['nullable', Rule::in(array_keys(Page::RECRUITMENT_STATUSES))],
            'type_data.rec_starts_at' => ['nullable', 'date'],
            'type_data.rec_ends_at' => ['nullable', 'date', 'after_or_equal:type_data.rec_starts_at'],
            'type_data.rec_places' => ['nullable', 'string', 'max:120'],
            'type_data.rec_mode' => ['nullable', 'string', 'max:200'],
            'type_data.rec_period' => ['nullable', 'string', 'max:120'],
            'type_data.rec_cost' => ['nullable', 'string', 'max:120'],
            'type_data.rec_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_-]+$/i'],
            'type_data.rec_accessibility' => ['nullable', 'string', 'max:3000'],
            'type_data.rec_results' => ['nullable', 'string', 'max:3000'],
            'type_data.bonus' => ['nullable', 'array', 'max:30'],
            'type_data.bonus.*.text' => ['nullable', 'string', 'max:300'],
            'type_data.bonus.*.points' => ['nullable', 'string', 'max:20'],
            'type_data.rec_faq' => ['nullable', 'array', 'max:40'],
            'type_data.rec_faq.*.q' => ['nullable', 'string', 'max:300'],
            'type_data.rec_faq.*.a' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function listKeys(): array
    {
        return ['audience', 'requirements', 'bonus', 'benefits', 'steps', 'docs', 'rec_faq'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.recruitment';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.recruitment';
    }
}
