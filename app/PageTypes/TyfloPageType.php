<?php

namespace App\PageTypes;

use App\Support\TyfloSchedule;
use Illuminate\Validation\Rule;

/**
 * Wspólna część typów „Konsultacje tyflo” i „Szkolenia tyflo”: osoby prowadzące
 * (`consultants`), zgłoszenie (formularz, kontakt, przycisk) i harmonogram terminów (`slots`)
 * w `type_data`. Klucze są wspólne dla obu typów (typ strony decyduje o etykietach w widoku).
 * Nie jest typem strony — rejestr wykrywa tylko klasy z app/PageTypes/Types.
 */
abstract class TyfloPageType extends PageType
{
    /** 'consultations' | 'trainings' — wybiera etykiety (TyfloSchedule::labels) i dodatkowe pola. */
    abstract public function kind(): string;

    public function usesTypeData(): bool
    {
        return true;
    }

    public function groupOrder(): int
    {
        return $this->kind() === 'trainings' ? 56 : 55;
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'audience', 'requirements', 'cta', 'contact') + [
            'type_data.tc_form' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_-]+$/i'],
            'type_data.tc_signup_note' => ['nullable', 'string', 'max:1000'],
            'type_data.tc_schedule_note' => ['nullable', 'string', 'max:1000'],
            'type_data.tc_accessibility' => ['nullable', 'string', 'max:2000'],
            'type_data.tt_duration' => ['nullable', 'string', 'max:160'],
            'type_data.tt_cost' => ['nullable', 'string', 'max:160'],
            'type_data.tt_group' => ['nullable', 'string', 'max:160'],
            'type_data.tt_certificate' => ['nullable', 'string', 'max:200'],
            'type_data.tt_program' => ['nullable', 'array', 'max:60'],
            'type_data.tt_program.*.title' => ['nullable', 'string', 'max:200'],
            'type_data.tt_program.*.text' => ['nullable', 'string', 'max:1000'],
            'type_data.consultants' => ['nullable', 'array', 'max:30'],
            'type_data.consultants.*.name' => ['nullable', 'string', 'max:120'],
            'type_data.consultants.*.role' => ['nullable', 'string', 'max:160'],
            'type_data.consultants.*.text' => ['nullable', 'string', 'max:600'],
            'type_data.consultants.*.email' => ['nullable', 'email', 'max:190'],
            'type_data.slots' => ['nullable', 'array', 'max:200'],
            'type_data.slots.*.date' => ['nullable', 'date'],
            'type_data.slots.*.date_to' => ['nullable', 'date'],
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
        return ['audience', 'requirements', 'tt_program', 'consultants', 'slots'];
    }
}
