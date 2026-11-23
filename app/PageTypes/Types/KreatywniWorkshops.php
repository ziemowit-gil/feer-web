<?php

namespace App\PageTypes\Types;

use App\Models\KreatywniWorkshop;
use App\Models\SiteSetting;
use App\PageTypes\PageType;

/**
 * „Kalendarz warsztatów” szablonu „kreatywni”: strona z terminami z mikro modułu „Warsztaty – Kreatywni”
 * (App\Models\KreatywniWorkshop) — nadchodzące w miesiącach, opcjonalnie minione. Do wyboru tylko w witrynie
 * w szablonie „kreatywni” z włączonym modułem „workshops” (Ustawienia → Moduły) i tabelą modułu; przy wyłączonym
 * module istniejąca strona zwraca 404 (PageController::show).
 */
class KreatywniWorkshops extends PageType
{
    public function key(): string
    {
        return 'kreatywni_workshops';
    }

    public function label(): string
    {
        return 'Kalendarz warsztatów (terminy z modułu „Warsztaty – Kreatywni”: miejsce, data, uczestnicy, opis, grafika)';
    }

    public function group(): string
    {
        return 'Kontakt i wydarzenia';
    }

    public function order(): int
    {
        return 31;
    }

    public function icon(): ?string
    {
        return 'fa-calendar-check';
    }

    public function selectable(): bool
    {
        $site = SiteSetting::current();

        return $site->site_template === 'kreatywni' && $site->isModuleEnabled('workshops') && KreatywniWorkshop::supported();
    }

    public function usesTypeData(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::sharedRules('lead') + [
            'type_data.show_past' => ['nullable', 'boolean'],
            // Widok: kalendarz miesiąca + lista (domyślnie), sam kalendarz albo sama lista.
            'type_data.view' => ['nullable', 'in:both,calendar,list'],
        ];
    }

    public function frontView(): ?string
    {
        return 'templates.kreatywni.partials.workshops-calendar';
    }

    public function adminView(): ?string
    {
        return 'admin.pages.partials.type-data.kreatywni-workshops';
    }
}
