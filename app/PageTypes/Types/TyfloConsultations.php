<?php

namespace App\PageTypes\Types;

use App\PageTypes\TyfloPageType;

class TyfloConsultations extends TyfloPageType
{
    public function key(): string
    {
        return 'tyflo_consultations';
    }

    public function kind(): string
    {
        return 'consultations';
    }

    public function label(): string
    {
        return 'Konsultacje tyflo (opis, konsultanci, zgłoszenie i harmonogram terminów)';
    }

    public function order(): int
    {
        return 300;
    }

    public function icon(): ?string
    {
        return 'fa-eye-low-vision';
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
