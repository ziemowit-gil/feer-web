<?php

namespace App\PageTypes\Types;

use App\PageTypes\TyfloPageType;

class TyfloTrainings extends TyfloPageType
{
    public function key(): string
    {
        return 'tyflo_trainings';
    }

    public function kind(): string
    {
        return 'trainings';
    }

    public function label(): string
    {
        return 'Szkolenia tyflo (program, prowadzący, wymagania, zgłoszenie i harmonogram terminów)';
    }

    public function order(): int
    {
        return 310;
    }

    public function icon(): ?string
    {
        return 'fa-chalkboard-user';
    }

    public function frontView(): ?string
    {
        return 'page.partials.typed.tyflo-trainings';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.tyflo-trainings';
    }
}
