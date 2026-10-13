<?php

namespace App\Models\Concerns;

use App\Models\FormDefinition;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

/**
 * Formularz zgłoszeń z SZO osadzany na stronie treści (oferta wolontariatu, wydarzenie, oferta pracy).
 *
 * Kolumny: `szo_form_slug` (formularz w SZO) i `form_definition_id` (jego lustro w CMS-ie,
 * zob. App\Services\SzoFormMirror). Zgłoszenia idą zwykłą ścieżką formularzy CMS → SZO.
 */
trait HasSzoForm
{
    /** Lustrzany formularz CMS formularza SZO. */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /** Formularz z SZO do osadzenia na stronie (aktywny) albo null. */
    public function szoForm(): ?FormDefinition
    {
        if (blank($this->szo_form_slug) || ! Route::has('formularz.store')) {
            return null;
        }

        $form = $this->formDefinition;

        return $form?->is_active ? $form : null;
    }
}
