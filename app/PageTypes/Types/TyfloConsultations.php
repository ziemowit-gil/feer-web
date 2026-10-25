<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;
use App\Support\TyfloSchedule;
use Illuminate\Validation\Rule;

class TyfloConsultations extends PageType
{
    public function key(): string
    {
        return 'tyflo_consultations';
    }

    public function label(): string
    {
        return 'Konsultacje tyflo (opis, konsultanci, zgłoszenie i harmonogram terminów)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 300;
    }

    public function groupOrder(): int
    {
        return 55;
    }

    public function icon(): ?string
    {
        return 'fa-eye-low-vision';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'audience', 'cta', 'contact') + [
            'type_data.tc_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_-]+$/i'],
            'type_data.tc_signup_note' => ['nullable', 'string', 'max:1000'],
            'type_data.tc_schedule_note' => ['nullable', 'string', 'max:1000'],
            'type_data.tc_accessibility' => ['nullable', 'string', 'max:2000'],
            'type_data.consultants' => ['nullable', 'array', 'max:30'],
            'type_data.consultants.*.name' => ['nullable', 'string', 'max:120'],
            'type_data.consultants.*.role' => ['nullable', 'string', 'max:160'],
            'type_data.consultants.*.text' => ['nullable', 'string', 'max:600'],
            'type_data.consultants.*.email' => ['nullable', 'email', 'max:190'],
            'type_data.slots' => ['nullable', 'array', 'max:200'],
            'type_data.slots.*.date' => ['nullable', 'date'],
            'type_data.slots.*.time_from' => ['nullable', 'date_format:H:i'],
            'type_data.slots.*.time_to' => ['nullable', 'date_format:H:i'],
            'type_data.slots.*.mode' => ['nullable', Rule::in(array_keys(TyfloSchedule::MODES))],
            'type_data.slots.*.place' => ['nullable', 'string', 'max:200'],
            'type_data.slots.*.consultant' => ['nullable', 'string', 'max:120'],
            'type_data.slots.*.seats' => ['nullable', 'string', 'max:40'],
            'type_data.slots.*.status' => ['nullable', Rule::in(array_keys(TyfloSchedule::STATUSES))],
            'type_data.slots.*.note' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function listKeys(): array
    {
        return ['audience', 'consultants', 'slots'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.tyflo-consultations';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.tyflo-consultations';
    }
}
