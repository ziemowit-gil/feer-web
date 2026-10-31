<?php

namespace App\PageTypes\Types;

use App\PageTypes\PageType;

class TrainingOffer extends PageType
{
    public function key(): string
    {
        return 'training_offer';
    }

    public function label(): string
    {
        return 'Szkolenie (program, terminy, cena, trener, zapisy)';
    }

    public function group(): string
    {
        return 'Oferta i edukacja';
    }

    public function order(): int
    {
        return 210;
    }

    public function groupOrder(): int
    {
        return 210;
    }

    public function icon(): ?string
    {
        return 'fa-chalkboard-user';
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead', 'cta', 'contact', 'audience') + [
            'type_data.category' => ['nullable', 'string', 'max:80'],
            'type_data.duration' => ['nullable', 'string', 'max:120'],
            'type_data.mode' => ['nullable', 'in:stationary,online,hybrid'],
            'type_data.place' => ['nullable', 'string', 'max:160'],
            'type_data.group_size' => ['nullable', 'string', 'max:80'],
            'type_data.price' => ['nullable', 'string', 'max:160'],
            'type_data.price_note' => ['nullable', 'string', 'max:300'],
            'type_data.certificate' => ['nullable', 'string', 'max:200'],
            'type_data.prerequisites' => ['nullable', 'string', 'max:2000'],
            'type_data.featured' => ['nullable', 'boolean'],
            'type_data.outcomes' => ['nullable', 'array'],
            'type_data.outcomes.*.text' => ['nullable', 'string', 'max:300'],
            'type_data.program' => ['nullable', 'array'],
            'type_data.program.*.title' => ['nullable', 'string', 'max:160'],
            'type_data.program.*.hours' => ['nullable', 'string', 'max:40'],
            'type_data.program.*.text' => ['nullable', 'string', 'max:1500'],
            'type_data.trainers' => ['nullable', 'array'],
            'type_data.trainers.*.name' => ['nullable', 'string', 'max:120'],
            'type_data.trainers.*.role' => ['nullable', 'string', 'max:120'],
            'type_data.trainers.*.url' => ['nullable', 'string', 'max:300'],
            'type_data.editions' => ['nullable', 'array'],
            'type_data.editions.*.date' => ['nullable', 'date'],
            'type_data.editions.*.place' => ['nullable', 'string', 'max:160'],
            'type_data.editions.*.status' => ['nullable', 'in:open,few,full,planned'],
            'type_data.editions.*.url' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function listKeys(): array
    {
        return ['audience', 'outcomes', 'program', 'trainers', 'editions'];
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.training-offer';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.training_offer';
    }
}
